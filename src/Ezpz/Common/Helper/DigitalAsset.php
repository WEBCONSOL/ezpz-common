<?php

namespace Ezpz\Common\Helper;

use Ezpz\Common\ApiGateway\Env;
use WC\Utilities\CustomResponse;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Ezpz\Common\FileSystem\InterfaceFileSystem;
use Ezpz\Common\Repository\DBTableConstants;
use Ezpz\Common\Utilities\Request;
use Ezpz\Common\Utilities\HostNames;
use WC\Utilities\EncodingUtil;
use WC\Utilities\FileUtil;
use WC\Utilities\Logger;
use WC\Utilities\StringUtil;

final class DigitalAsset
{
    private $em;
    private $conn;
    private $request;

    public function __construct(EntityManager $em){
        $this->em = $em;
        $this->conn = $this->em->getConnection();
        $this->request = new Request();
    }

    public function parentId(): int {
        $parentId = $this->request->getParam('parent_id', 0);
        if (!$parentId) {
            $parentId = $this->request->getParam(HEADER_PARENT_ID, 0);
        }
        if ($parentId > 0) {
            try {
                $query = 'SELECT id FROM '.DBTableConstants::DA_ASSETS.' WHERE id=' . $parentId;
                $result = $this->conn->executeQuery($query)->fetch(\PDO::FETCH_ASSOC);
                if (!empty($result)) {
                    $parentId = (int)$result['id'];
                }
            }
            catch (DBALException $e) {
                CustomResponse::render($e->getCode(), $e->getMessage());
            }
        }
        return $parentId;
    }

    public function deleteFile(array $ids, InterfaceFileSystem $fileSystem) {

        $asAssetsFileSystemDeleteIds = array();
        $paths = array();

        try {
            $query = 'SELECT b.path,a.asset_fs_id'.
                ' FROM '.DBTableConstants::DA_ASSETS.' a LEFT JOIN '.DBTableConstants::DA_ASSETS_FILES.' b ON b.id=a.asset_fs_id ' .
                ' WHERE a.id IN (' . implode(',', $ids) . ')';
            $results = $this->conn->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
            if (sizeof($results) > 0) {
                foreach ($results as $result) {
                    $asAssetsFileSystemDeleteIds[$result['asset_fs_id']] = $result['asset_fs_id'];
                    $paths[$result['asset_fs_id']] = $result['path'];
                }
                unset($result);
            }

            // delete from ds_assets
            $this->deleteFromDSAssetsTable($ids);

            // delete from ds_assets_files
            $this->deleteFromDSAssetsFilesTable($asAssetsFileSystemDeleteIds);

            // remove file from physical file system
            if (sizeof($asAssetsFileSystemDeleteIds) > 0) {
                foreach ($paths as $id=>$path) {
                    if (isset($asAssetsFileSystemDeleteIds[$id])) {
                        if ($fileSystem->has($path)) {
                            $fileSystem->delete($path);
                        }
                    }
                }
                unset($id, $path);
            }

            // free memory
            unset($query, $results, $paths, $asAssetsFileSystemDeleteIds, $ids);
        }
        catch (DBALException $e) {
            CustomResponse::render($e->getCode(), $e->getMessage());
        }

        return true;
    }

    /**
     * @param array               $ids
     * @param InterfaceFileSystem $fileSystem
     *
     * @return bool
     */
    public function deleteFolder(array $ids, InterfaceFileSystem $fileSystem) {

        try {
            $asAssetsDeleteIds = $ids;
            $asAssetsFileSystemDeleteIds = array();
            $paths = array();

            $queryChildren = 'SELECT a.id,b.path,a.asset_fs_id FROM' .
                ' '.DBTableConstants::DA_ASSETS.' a LEFT JOIN '.DBTableConstants::DA_ASSETS_FILES.' b ON b.id=a.asset_fs_id' .
                ' WHERE a.parent_id IN (' . implode(',', $ids) . ')';
            $children = $this->conn->executeQuery($queryChildren)->fetchAll(\PDO::FETCH_ASSOC);
            if ($children) {
                foreach ($children as $child) {
                    $asAssetsDeleteIds[] = $child['id'];
                    if ($child['asset_fs_id'] && $child['path']) {
                        $asAssetsFileSystemDeleteIds[$child['asset_fs_id']] = $child['asset_fs_id'];
                        $paths[$child['asset_fs_id']] = $child['path'];
                    }
                }
                unset($child);
            }

            // delete from ds_assets
            $this->deleteFromDSAssetsTable($asAssetsDeleteIds);

            // delete from ds_assets_files (if applicable)
            $this->deleteFromDSAssetsFilesTable($asAssetsFileSystemDeleteIds);

            // remove file from physical file system
            if (sizeof($asAssetsFileSystemDeleteIds) > 0) {
                foreach ($paths as $id=>$path) {
                    if (isset($asAssetsFileSystemDeleteIds[$id])) {
                        if ($fileSystem->has($path)) {
                            $fileSystem->delete($path);
                        }
                    }
                }
                unset($id, $path);
            }

            // free memory
            unset($queryChildren, $children, $paths, $asAssetsDeleteIds, $asAssetsFileSystemDeleteIds, $ids);
        }
        catch (DBALException $e) {
            CustomResponse::render($e->getCode(), $e->getMessage());
        }

        return true;
    }

    /**
     * @param array $ids
     */
    private function deleteFromDSAssetsTable(array $ids) {
        if (sizeof($ids) > 0) {
            try {
                $query = 'DELETE FROM '.DBTableConstants::DA_ASSETS.' WHERE id IN('.implode(',', $ids).')';
                $this->conn->exec($query);
                unset($query);
            }
            catch (DBALException $e) {
                CustomResponse::render($e->getCode(), $e->getMessage());
            }
        }
    }

    /**
     * @param array $ids
     */
    private function deleteFromDSAssetsFilesTable(array &$ids) {
        if (sizeof($ids) > 0) {
            try {
                // exclude other digital assets that referenced to it
                $query = 'SELECT asset_fs_id FROM '.DBTableConstants::DA_ASSETS.' WHERE asset_fs_id IN(' . implode(',', $ids) . ')';
                $results = $this->conn->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
                if (sizeof($results) > 0) {
                    foreach ($results as $result) {
                        unset($ids[$result['asset_fs_id']]);
                    }
                    unset($result);
                }
                if (sizeof($ids) > 0) {
                    $query = 'DELETE FROM '.DBTableConstants::DA_ASSETS_FILES.' WHERE id IN('.implode(',', $ids).')';
                    $this->conn->exec($query);
                }
                unset($query, $results);
            }
            catch (DBALException $e) {
                CustomResponse::render($e->getCode(), $e->getMessage());
            }
        }
    }

    public static function processResult(array &$list) {
        if (isset($list['id'])) {
            if (!isset($list['is_file'])) {$list['is_file']=false;}
            if (!isset($list['is_folder'])) {$list['is_folder']=false;}
            if (!isset($list['is_image'])) {$list['is_image']=false;}
            if (isset($list['attrs']) && EncodingUtil::isValidJSON($list['attrs'])) {$list['attrs'] = json_decode($list['attrs'], true);}
            foreach ($list as $key=>$item) {
                if ($item) {
                    if (EncodingUtil::isValidJSON($item)) {
                        $item = json_decode($item, true);
                        if (isset($item['is_image']) && $item['is_image']) {
                            $item['is_image'] = true;
                        }
                    }
                    else if ($key === 'path') {
                        $list[$key] = self::toWebPath($item);
                    }
                    else if ($key === 'type') {
                        if ($item === 'image') {
                            $list['is_image'] = true;
                            $list['is_file'] = true;
                        }
                        else if ($item === 'folder') {
                            $list['is_folder'] = true;
                        }
                        else if ($item === 'file') {
                            $list['is_file'] = true;
                        }
                    }
                    else if ($key === 'num_children') {
                        $list[$key] = (int)$item;
                    }
                }
            }
            if (!empty($list['attrs'])) {
                if (isset($list['attrs']['is_image']) && $list['attrs']['is_image']) {
                    $list['is_image'] = $list['attrs']['is_image'];
                    $list['type'] = 'image';
                }
                if (isset($list['attrs']['extension']) && in_array(strtolower($list['attrs']['extension']), ['jpeg','jpg','gif','tif','tiff','png','svg'])) {
                    $list['is_image'] = true;
                    $list['is_file'] = true;
                    $list['type'] = 'image';
                }
            }
        }
        else if (isset($list[0]) && isset($list[0]['id'])) {
            foreach ($list as $i=>$item) {
                self::processResult($list[$i]);
            }
        }
    }

    public static function toWebPath(string $path): string {
        if (!StringUtil::startsWith($path, '//') && !StringUtil::startsWith($path, 'http://') && !StringUtil::startsWith($path, 'https://')) {
            $host = HostNames::getAssets();
            if (strpos($host, EZPZ_USERNAME) === false) {
                $host = $host . '/' . EZPZ_USERNAME;
            }
            return str_replace(
                Env::ENV_HOST_SCHEMAS,
                EZPZ_ENV,
                str_replace(array('http://','https://'), '//', $host) . '/'. ltrim($path, '/')
            );
        }
        return $path;
    }
}