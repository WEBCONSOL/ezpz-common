<?php

namespace Ezpz\Common\Helper;

class AddressHelper
{
    private function __construct(){}

    public static function format(array &$items) {
        if (isset($items['id']) && $items['id'] > 0) {
            self::country($items);
            self::addr($items);
            self::telephone($items);
        }
        else if (isset($items[0]) && isset($items[0]['id']) && $items[0]['id'] > 0) {
            foreach ($items as $i=>$item) {
                self::format($items[$i]);
            }
        }
    }

    private static function country(array &$items) {
        if (isset($items['country']) && !is_array($items['country'])) {
            $items['country'] = [
                'id' => $items['addr_country'],
                'name' => isset($items['country_name']) ? $items['country_name'] : (isset($items['name']) ? $items['name'] : ''),
                'code_2' => isset($items['code_2']) ? $items['code_2'] : '',
                'code_3' => isset($items['code_3']) ? $items['code_3'] : ''
            ];
        }
        else if (isset($items['addr_country'])) {
            $items['country'] = [
                'id' => $items['addr_country'],
                'name' => isset($items['country_name']) ? $items['country_name'] : '',
                'code_2' => isset($items['code_2']) ? $items['code_2'] : '',
                'code_3' => isset($items['code_3']) ? $items['code_3'] : ''
            ];
        }
        if (isset($items['addr_country'])) {unset($items['addr_country']);}
        if (isset($items['country_name'])) {unset($items['country_name']);}
        if (isset($items['code_2'])) {unset($items['code_2']);}
        if (isset($items['code_3'])) {unset($items['code_3']);}
    }

    private static function addr(array &$items) {
        if (isset($items['addr_street'])) {
            $items['addr'] = [
                'street' => isset($items['addr_street'])?$items['addr_street']:'',
                'city' => isset($items['addr_city'])?$items['addr_city']:'',
                'province' => isset($items['addr_province'])?$items['addr_province']:'',
                'postalcode' => isset($items['addr_postalcode'])?$items['addr_postalcode']:'',
            ];
            if (isset($items['country'])) {
                $items['addr']['country'] = $items['country'];
            }
            unset($items['addr_street'],$items['addr_city'],$items['addr_province'],$items['addr_postalcode']);
        }
    }

    private static function telephone(array &$items) {
        if (isset($items['telephone_type'])) {
            if (isset($items['telephone'])) {
                if (is_string($items['telephone'])) {
                    $parts1 = explode(',', str_replace(' ', '', $items['telephone']));
                    $parts2 = explode('-', $parts1[0]);
                    $items['telephone'] = [
                        'type' => $items['telephone_type'],
                        'country' => isset($parts2[0]) ? $parts2[0] : '',
                        'area' => isset($parts2[1]) ? $parts2[1] : '',
                        'number' => isset($parts2[2]) ? $parts2[2] : '',
                        'ext' => sizeof($parts1) > 1 ? $parts1[sizeof($parts1)-1] : ''
                    ];
                }
                else {
                    $items['telephone']['type'] = $items['telephone_type'];
                }
            }
            unset($items['telephone_type']);
        }
    }
}