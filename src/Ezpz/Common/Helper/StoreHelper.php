<?php

namespace Ezpz\Common\Helper;

use Ezpz\Common\Repository\DataService;
use Ezpz\Common\Repository\DBTableConstants;

class StoreHelper
{
    private function __construct() {}

    /**
     * @param array       $items
     * @param DataService $dataService
     */
    public static function properties(array &$items, DataService &$dataService) {
        if (isset($items['params']) || isset($items['entities'])) {
            $key = isset($items['params']) ? 'params' : 'entities';
            if ($items[$key]) {
                foreach ($items[$key] as $k=> $entity) {$items[$k] = $entity;}
            }
            if (isset($items['address'])) {
                AddressHelper::format($items['address']);
            }
            unset($items[$key]);
        }
        else if (isset($items[0]) && (isset($items[0]['params']) || isset($items[0]['entities']))) {
            $key = isset($items[0]['params']) ? 'params' : 'entities';
            foreach ($items as $i=> $value) {
                if ($value[$key]) {
                    self::properties($items[$i], $dataService);
                }
            }
        }
        self::fetchTaxes($items, $dataService);
    }

    /**
     * @param array         $items
     * @param DataService $dataService
     */
    private static function fetchTaxes(array &$items, DataService &$dataService) {
        if (isset($items['id'])) {
            $items['taxes'] = $dataService->getStoreTaxes([$items['id']]);
        }
        else if (isset($items[0]) && isset($items[0]['id'])) {
            $store_ids = [];
            foreach ($items as $i=>$item) {
                $store_ids[] = $item['id'];
                $items[$i]['taxes'] = [];
            }
            $taxes = $dataService->getStoreTaxes($store_ids);
            if (!empty($taxes)) {
                foreach ($taxes as $tax) {
                    self::setTax($items, $tax);
                }
            }
        }
    }

    private static function setTax(array &$items, array &$tax) {
        foreach ($items as $i=>$store) {
            if ((int)$tax['store_id'] === (int)$store['id']) {
                $items[$i]['taxes'][] = $tax;
            }
        }
    }
}