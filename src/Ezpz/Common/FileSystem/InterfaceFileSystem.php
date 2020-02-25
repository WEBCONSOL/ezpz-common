<?php

namespace Ezpz\Common\FileSystem;

interface InterfaceFileSystem
{
    public function folderPath($title);

    public function filePath($file, $ext);

    public function checksum($path);

    public function createFolder($path);

    public function uploadFile($buffer, $path);

    public function delete($path);

    public function getExtension($arg);

    public function getFileType($arg);

    public function has($path);
}