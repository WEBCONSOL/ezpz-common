<?php

namespace Ezpz\Common\FileSystem;

use League\Flysystem\Adapter\Local;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\Filesystem as FileSystemLib;
use Ezpz\Common\Utilities\HostNames;
use WC\Utilities\CustomResponse;
use WC\Utilities\FileUtil;
use WC\Utilities\PathUtil;
use WC\Utilities\StringUtil;

class LocalFileSystem implements InterfaceFileSystem
{
    private $flySystem = null;
    private $username = null;
    private $fsRoot = null;
    private $pathLength = 2;

    function __construct($username=null) {
        $this->username = $username;
        if ($this->username) {
            $this->loadFileSystemLib();
        }
    }

    private function loadFileSystemLib() {
        $this->fsRoot = PATH_SERVICE_ASSETS . '/data/' . $this->username;
    }

    public function folderPath($title) {
        $a = md5(PathUtil::toSlug($title));
        $b[] = substr($a, 0*$this->pathLength, $this->pathLength);
        $b[] = substr($a, 1*$this->pathLength, $this->pathLength);
        $b[] = substr($a, 2*$this->pathLength, $this->pathLength);
        return implode('/', $b) . '/' . $a;
    }

    public function filePath($file, $ext) {
        if ($this->isToBeUploaded($file)) {
            $a = md5_file($file) . '.' . $ext;
        }
        else {
            CustomResponse::render(500, 'Illegal file');
        }
        $b[] = substr($a, 0*$this->pathLength, $this->pathLength);
        $b[] = substr($a, 1*$this->pathLength, $this->pathLength);
        $b[] = substr($a, 2*$this->pathLength, $this->pathLength);
        return implode('/', $b) . '/' . $a;
    }

    public function checksum($path) {
        $ret = '';
        if (is_string($path)) {
            if (explode('/', $path) > 1) {
                $ret = md5($path);
            }
        }
        if (!$ret) {
            CustomResponse::render(500, 'Illegal path  to generate checksum');
        }
        return $ret;
    }

    public function isToBeUploaded($path) {
        $buffer = @file_get_contents($path);
        return $buffer && strpos($path, 'tmp') !== false;
    }

    public function createFolder($path) {
        $parts = explode('/', $this->pathWithoutRootDir($path));
        $a = '';
        foreach ($parts as $part) {
            $a = $a . ($a ? '/' : '') . $part;
            if (!$this->has($a)) {
                $this->getFlySystem()->createDir($a);
            }
        }
        return true;
    }

    public function uploadFile($buffer, $path) {

        $path = $this->pathWithoutRootDir($path);
        $parts = explode('/', $path);
        unset($parts[sizeof($parts) - 1]);
        $this->getFlySystem()->createDir(implode('/', $parts));
        $ret = false;

        if (is_string($buffer)) {
            if ($this->isToBeUploaded($buffer)) {
                $buffer = fopen($buffer, "r+");
                try {
                    $ret = $this->getFlySystem()->writeStream($path, $buffer);
                }
                catch (FileExistsException $e) {
                    CustomResponse::render($e->getCode(), $e->getMessage());
                }
            }
            else {
                try {
                    $ret = $this->getFlySystem()->write($path, $buffer);
                }
                catch (FileExistsException $e) {
                    CustomResponse::render($e->getCode(), $e->getMessage());
                }
            }
        }
        else if (is_resource($buffer)) {
            try {
                $ret = $this->getFlySystem()->writeStream($path, $buffer);
            }
            catch (FileExistsException $e) {
                CustomResponse::render($e->getCode(), $e->getMessage());
            }
        }
        else {
            CustomResponse::render(500, gettype($buffer) . " is unsupported for uploading.");
        }

        return $ret;
    }

    public function delete($path) {

        if ($path) {
            if ($this->isDir($path)) {
                $this->getFlySystem()->deleteDir($path);
            }
            else if ($this->isFile($path)) {
                try {
                    $this->getFlySystem()->delete($path);
                }
                catch (FileNotFoundException $e) {
                    CustomResponse::render($e->getCode(), $e->getMessage());
                }
            }
            $path = $this->pathWithoutRootDir($path);
            $parts = explode('/', $path);
            $size = sizeof($parts);
            if ($size > 0 && $parts[0]) {
                unset($parts[$size-1]);
                $path = implode('/', $parts);
                if (!$this->hasNodes($path)) {
                    return $this->delete($path);
                }
            }
        }
        return true;
    }

    public function getExtension($arg)
    {
        $ext = null;
        if (is_array($arg) && isset($arg['name'])) {
            $ext = \WC\Utilities\FileUtil::getExtension($arg['name']);
        }
        else if (is_string($arg)) {
            $ext = \WC\Utilities\FileUtil::getExtension($arg);
        }
        if (!$ext && isset($arg['tmp_name'])) {
            try {
                $ext = FileUtil::mime2ext(mime_content_type($arg['tmp_name']));
            }
            catch (\Exception $e) {}
        }
        return $ext;
    }

    public function getFileType($arg)
    {
        $type = 'doc';
        $mimetype = null;
        if (is_array($arg) && isset($arg['type'])) {
            $mimetype = $arg['type'];
        }
        else if (is_string($arg)) {
            $mimetype = $arg;
        }
        if ($mimetype) {
            $parts = explode('/', $mimetype);
            if ((sizeof($parts) > 1 && $parts[0] === 'image') || (strpos($mimetype, 'image') !== false)) {
                $type = 'picture';
            }
        }
        return $type;
    }

    public function pathWithoutRootDir($path, $trim=true) {
        if ($trim === 'left') {
            return ltrim($this->removeDoubleSlashes(str_replace($this->fsRoot, '', $this->removeDoubleSlashes($path))), '/');
        }
        else if ($trim === 'right') {
            return rtrim($this->removeDoubleSlashes(str_replace($this->fsRoot, '', $this->removeDoubleSlashes($path))), '/');
        }
        else {
            return trim($this->removeDoubleSlashes(str_replace($this->fsRoot, '', $this->removeDoubleSlashes($path))), '/');
        }
    }

    public function removeDoubleSlashes($path) {return StringUtil::removeDoubleSlashes($path);}

    public function has($path) {
        if ($this->isDir($path)) {
            return $this->getFlySystem()->createDir($this->pathWithoutRootDir($path));
        }
        else {
            return $this->getFlySystem()->has($this->pathWithoutRootDir($path));
        }
    }

    public function isFile($path) {$parts2 = explode('.', $this->lastParts($path));return sizeof($parts2) === 2 || $this->isToBeUploaded($path);}

    public function isDir($path) {$parts2 = explode('.', $this->lastParts($path));return sizeof($parts2) === 1;}

    public function lastParts($path) {$parts1 = explode('/', $path);return end($parts1);}

    public function toWebPath(string $path): string {
        $host = HostNames::get('assets');
        if (strpos($host, EZPZ_USERNAME) === false) {
            $host = $host . '/' . EZPZ_USERNAME;
        }
        return str_replace(array('http://','https://'), '//', $host) . '/'. ltrim($path, '/');
        //return HostNames::get('assets') . '/'. ltrim($path, '/');
    }

    public function hasNodes($path) {
        $list = $this->getFlySystem()->listContents($path);
        return sizeof($list) > 0 && sizeof($list[0]);
    }

    private function getFlySystem() {
        if ($this->flySystem === null) {
            $adapter = new Local($this->fsRoot);
            $this->flySystem = new FileSystemLib($adapter);
        }
        return $this->flySystem;
    }
}