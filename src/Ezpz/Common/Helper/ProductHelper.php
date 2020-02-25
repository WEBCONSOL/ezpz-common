<?php

namespace Ezpz\Common\Helper;

use Repository\DataService;
use Repository\DBTableConstants;
use WC\Utilities\DateTimeFormat;
use WC\Utilities\EncodingUtil;
use WC\Utilities\Logger;

class ProductHelper
{
    public static $PRODUCT_MODES = ['physical'=>'physical','download'=>'download','subscription'=>'subscription'];

    private function __construct() {}

    public static function isPhysicalProduct(string $mode): bool {return $mode===self::$PRODUCT_MODES['physical'];}
    public static function isDownloadProduct(string $mode): bool {return $mode===self::$PRODUCT_MODES['download'];}
    public static function isSubscriptionProduct(string $mode): bool {return $mode===self::$PRODUCT_MODES['subscription'];}
    public static function removeEmptyElement(array &$list) {foreach ($list as $i=>$item) {if(empty($item)) unset($list[$i]);}}

    /**
     * @param array       $items
     * @param array       $product_type_fields
     * @param DataService $dataService
     */
    public static function formatProperties(array &$items, array $product_type_fields, DataService $dataService) {
        self::params($items, $dataService);
        self::formatProductType($items, $product_type_fields);
        self::publishDates($items);
        self::entities($items);
        self::loadEntities($items, $dataService);
        self::processAsset($items);
    }

    private static function processAsset(array &$items) {
        if (isset($items['asset']) && $items['asset']) {
            foreach ($items['asset'] as $i=>$asset) {
                if (!is_array($items['asset'][$i])) {
                    if (EncodingUtil::isValidJSON($items['asset'][$i])) {
                        $items['asset'][$i] = json_decode($items['asset'][$i], true);
                    }
                }
                if (is_array($items['asset'][$i])) {
                    DigitalAsset::processResult($items['asset'][$i]);
                }
            }
        }
        else if (isset($items[0])) {
            foreach ($items as $i=>$item) {
                self::processAsset($items[$i]);
            }
        }
    }

    private static function params(array &$items, DataService $dataService) {
        if (isset($items['params']) && $items['params']) {
            if (is_string($items['params']) && EncodingUtil::isValidJSON($items['params'])) {
                $items['params'] = json_decode($items['params'], true);
            }
            $items['entities'] = isset($items['params']['entities']) ? $items['params']['entities'] : [];
            $items['product_attrs'] = isset($items['params']['product_attrs']) ? $items['params']['product_attrs'] : $items['params'];
            // format subscription
            self::formatSubscription($items, $dataService);
            unset($items['params']);
        }
        else if (isset($items[0]) && isset($items[0]['params'])) {
            foreach ($items as $i=>$item) {
                self::params($items[$i], $dataService);
            }
        }
    }

    private static function formatProductType(array &$items, array $product_type_fields) {
        if (isset($items['product_type']) && !empty($product_type_fields)) {
            $product_type = explode('<|>', $items['product_type']);
            $items['product_type'] = [];
            foreach ($product_type_fields as $i=>$field) {
                $field = str_replace('b.', '', $field);
                if ($field === 'product_attrs') {
                    if (is_string($product_type[$i])) {
                        $product_type[$i] = json_decode($product_type[$i], true);
                    }
                    foreach ($product_type[$i] as $j=>$product_attr) {
                        if (isset($product_attr['params']) && is_string($product_attr['params'])) {
                            $product_type[$i][$j]['params'] = json_decode($product_type[$i][$j]['params'], true);
                        }
                        if (isset($items['product_attrs']) && isset($product_type[$i][$j]['params']['name']) && isset($items['product_attrs'][$product_type[$i][$j]['params']['name']])) {
                            $product_type[$i][$j]['value'] = $items['product_attrs'][$product_type[$i][$j]['params']['name']]['value'];
                            self::displayValue($product_type[$i][$j]['value'], $product_type[$i][$j]);
                        }
                    }
                }
                $items['product_type'][$field] = $product_type[$i];
            }
        }
        else if (isset($items[0]) && isset($items[0]['product_type']) && $items[0]['product_type']) {
            foreach ($items as $i=>$item) {
                self::formatProductType($items[$i], $product_type_fields);
            }
        }
    }

    private static function displayValue($val, array &$item) {
        if (is_array($val)) {
            if (isset($item['params']) && isset($item['params']['config']) &&
                isset($item['params']['config']['key'])) {
                foreach ($val as $i=>$v) {
                    $key = array_search($v, $item['params']['config']['key']);
                    if ($key && isset($item['params']['config']['value'][$key])) {
                        $item['displayValue'][$i] = $item['params']['config']['value'][$key];
                    }
                }
            }
        }
        else if (isset($item['params']) && isset($item['params']['config']) &&
            isset($item['params']['config']['key'])) {
            $key = array_search($val, $item['params']['config']['key']);
            if ($key && isset($item['params']['config']['value'][$key])) {
                $item['displayValue'] = $item['params']['config']['value'][$key];
            }
        }
    }

    private static function publishDates(array &$items) {
        if (isset($items['published_start'])) {
            if ($items['published_start']) {
                $items['published_start'] = date(DateTimeFormat::getStandardFormatString(), $items['published_start']);
            }
            if ($items['published_end']) {
                $items['published_end'] = date(DateTimeFormat::getStandardFormatString(), $items['published_end']);
            }
        }
        else if (isset($items[0]) && isset($items[0]['published_start'])) {
            foreach ($items as $i=>$item) {
                self::publishDates($items[$i]);
            }
        }
    }

    private static function entities(array &$item) {
        if (isset($item['params']) && isset($item['params']['entities']) &&
            (!isset($item['entities']) || empty($item['entities']))) {
            $item['entities'] = $item['params']['entities'];
        }
        if (isset($item['entities'])) {
            if (is_array($item['entities']) && !empty($item['entities'])) {
                foreach ($item['entities'] as $key=>$entity) {
                    if (($key === 'currency' || $key === 'country') && $entity['params']) {
                        if (is_string($entity['params']) && EncodingUtil::isValidJSON($entity['params'])) {
                            $entity['params'] = json_decode($entity['params'], true);
                        }
                        $entity['name'] = $entity['params']['name'];
                    }
                    self::formatAddress($entity);
                    if ($key === 'store') {$item['stores'] = !isset($entity[0]) ? [$entity] : $entity;}
                    else if ($key === 'category') {$item['categories'] = !isset($entity[0]) ? [$entity] : $entity;}
                    else if ($key === 'currency') {$item['currency'] = $entity;}
                    else {$item[$key] = !isset($entity[0]) ? [$entity] : $entity;}
                }
            }
            unset($item['entities']);
        }
        else if (isset($item[0])) {
            foreach ($item as $i=>$value) {
                if (isset($value['entities']) && !empty($value['entities'])) {
                    self::entities($item[$i]);
                }
            }
        }
    }

    private static function formatAddress(array &$item) {
        if (isset($item['params']) && isset($item['params']['address'])) {
            AddressHelper::format($item['params']['address']);
        }
        else if (isset($item[0]) && isset($item[0]['params'])) {
            foreach ($item as $i=>$value) {
                self::formatAddress($item[$i]);
            }
        }
    }

    private static function formatSubscription(&$items, DataService $dataService) {
        if (isset($items['params']) && isset($items['params']['subscription'])) {
            $items['subscription'] = $items['params']['subscription'];
            if (isset($items['subscription']['plan'])) {
                $items['subscription']['plan'] = $dataService->getGlobalPropertyById((int)$items['subscription']['plan']);
                if (!empty($items['subscription']['plan'])) {
                    $items['subscription']['plan']['name'] = $items['subscription']['plan']['config_value'];
                }
            }
        }
        else if (isset($items['subscription']) && isset($items['subscription']['plan'])) {
            $items['subscription']['plan'] = $dataService->getGlobalPropertyById((int)$items['subscription']['plan']);
            if (!empty($items['subscription']['plan'])) {
                $items['subscription']['plan']['name'] = $items['subscription']['plan']['config_value'];
            }
        }
    }

    private static function loadEntities(array &$items, DataService $dataService) {
        if (!isset($items[0])) {
            $id = isset($items['id']) ? $items['id'] : 0;
            if ($id > 0) {
                if (!isset($items['stores']) || !isset($items['categories']) || !isset($items['currency'])) {
                    $sql = 'SELECT entity_id,entity FROM '.DBTableConstants::ECOM_PRODUCT_ENTITIES.' WHERE product_id="'.$id.'"';
                    $rows = $dataService->fetchRows($sql);
                    $e = [];
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            if (!isset($entities[$row['entity']])) {
                                $e[$row['entity']] = [];
                            }
                            $e[$row['entity']][] = $row['entity_id'];
                        }
                    }
                    if (!isset($items['stores']) && isset($e['store'])) {
                        if (!isset($items['entities'])) {$items['entities'] = [];}
                        $sql = 'SELECT * FROM '.DBTableConstants::ECOM_STORES.' WHERE id IN("'.implode('","', $e['store']).'")';
                        $items['entities']['store'] = $dataService->fetchRows($sql);
                        StoreHelper::properties($items['entities']['store'], $dataService);
                    }
                    if (!isset($items['currency']) && isset($e['currency'])) {
                        if (!isset($items['entities'])) {$items['entities'] = [];}
                        $sql = 'SELECT * FROM '.DBTableConstants::ECOM_GLOBALPROPERTIES.' WHERE id IN("'.implode('","', $e['currency']).'")';
                        $items['entities']['currency'] = $dataService->fetchRow($sql);
                    }
                    if (!isset($items['categories']) && isset($e['category'])) {
                        if (!isset($items['entities'])) {$items['entities'] = [];}
                        $sql = 'SELECT * FROM '.DBTableConstants::ECOM_CATEGORIES.' WHERE id IN("'.implode('","', $e['category']).'")';
                        $items['entities']['category'] = $dataService->fetchRows($sql);
                    }
                    self::entities($items);
                }
            }
        }
        else {
            $entitiesMissing = ['stores'=>[],'categories'=>[],'currency'=>[]];
            $ids2 = [];
            foreach ($items as $i=>$item) {
                if (!isset($item['stores'])) {$entitiesMissing['stores'][] = $item['id'];$ids2[]=$item['id'];}
                if (!isset($item['categories'])) {$entitiesMissing['categories'] = $item['id'];$ids2[]=$item['id'];}
                if (!isset($item['currency'])) {$entitiesMissing['currency'] = $item['id'];$ids2[]=$item['id'];}
            }
            $ids2 = array_unique($ids2);
            $sql = 'SELECT product_id,entity_id,entity FROM '.DBTableConstants::ECOM_PRODUCT_ENTITIES.' WHERE product_id IN("'.implode('","', $ids2).'")';
            $rows = $dataService->fetchRows($sql);
            if (!empty($rows)) {
                $productEntities = [];
                $reformatEntities = false;
                foreach ($rows as $row) {
                    $k= $row['entity'].'_'.$row['entity_id'];
                    if (!isset($productEntities[$row['entity']])) {$productEntities[$row['entity']] = [];}
                    if (!isset($productEntities[$k])) {$productEntities[$k][$row['product_id']] = [];}
                    $productEntities[$row['entity']][] = $row['entity_id'];
                    $productEntities[$k][$row['product_id']] = $row['product_id'];
                }
                if (!empty($entitiesMissing['stores']) && isset($productEntities['store']) && !empty($productEntities['store'])) {
                    $productEntities['store'] = array_unique($productEntities['store']);
                    $sql = 'SELECT * FROM '.DBTableConstants::ECOM_STORES.' WHERE id IN("'.implode('","', $productEntities['store']).'")';
                    $rows = $dataService->fetchRows($sql);
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            $k = 'store_'.$row['id'];
                            if (isset($productEntities[$k])) {
                                foreach ($items as $i=>$item) {
                                    if (isset($productEntities[$k][$item['id']])) {
                                        if (!isset($items[$i]['entities'])) {$items[$i]['entities'] = [];}
                                        StoreHelper::properties($row, $dataService);
                                        $items[$i]['entities']['store'][] = $row;
                                        $reformatEntities = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($entitiesMissing['categories']) && isset($productEntities['category']) && !empty($productEntities['category'])) {
                    $sql = 'SELECT * FROM '.DBTableConstants::ECOM_CATEGORIES.' WHERE id IN("'.implode('","', $productEntities['category']).'")';
                    $rows = $dataService->fetchRows($sql);
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            $k = 'category_'.$row['id'];
                            if (isset($productEntities[$k])) {
                                foreach ($items as $i=>$item) {
                                    if (isset($productEntities[$k][$item['id']])) {
                                        if (!isset($items[$i]['entities'])) {$items[$i]['entities'] = [];}
                                        $items[$i]['entities']['category'][] = $row;
                                        $reformatEntities = true;
                                    }
                                }
                            }
                        }
                    }
                }
                if (!empty($entitiesMissing['currency']) && isset($productEntities['currency']) && !empty($productEntities['currency'])) {
                    $sql = 'SELECT * FROM '.DBTableConstants::ECOM_GLOBALPROPERTIES.' WHERE id IN("'.implode('","', $productEntities['currency']).'")';
                    $row = $dataService->fetchRow($sql);
                    if (!empty($row) && isset($productEntities['currency_'.$row['id']])) {
                        $k = 'currency_'.$row['id'];
                        foreach ($items as $i=>$item) {
                            if (isset($productEntities[$k][$item['id']])) {
                                if (!isset($items[$i]['entities'])) {$items[$i]['entities'] = [];}
                                $items[$i]['entities']['currency'] = $row;
                                $reformatEntities = true;
                            }
                        }
                    }
                }
                if ($reformatEntities) {self::entities($items);}
            }
        }

        if (isset($items['stores']) && isset($items['stores'][0]) && !isset($items['stores'][0]['taxes'])) {
            foreach ($items['stores'] as $i=>$store) {
                StoreHelper::properties($items['stores'][$i], $dataService);
            }
        }
    }
}