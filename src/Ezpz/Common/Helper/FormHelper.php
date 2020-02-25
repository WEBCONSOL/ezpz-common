<?php

namespace Ezpz\Common\Helper;

use Repository\DataService;

class FormHelper
{
    private function __construct() {}

    public static function getLength(int $id, DataService $dataService): array {
        $data = $dataService->getGlobalPropertyById($id);
        if (!empty($data)) {
            return ['id'=>$id, 'name'=>$data['config_value']];
        }
        return [];
    }

    public static function getTemperature(int $id, DataService $dataService): array {
        $data = $dataService->getGlobalPropertyById($id);
        if (!empty($data)) {
            return ['id'=>$id, 'name'=>$data['config_value']];
        }
        return [];
    }

    public static function getWeight(int $id, DataService $dataService): array {
        $data = $dataService->getGlobalPropertyById($id);
        if (!empty($data)) {
            return ['id'=>$id, 'name'=>$data['config_value']];
        }
        return [];
    }

    public static function getContact(int $id, DataService $dataService, array $country=array()): array {
        $data = $dataService->getContactById($id);
        if (!empty($data)) {
            if (!empty($country)) {
                $data['country'] = $country;
            }
            else if ($data['addr_country'] > 0) {
                $data['country'] = $dataService->getCountryByGlobalPropertyId((int)$data['addr_country']);
            }
            AddressHelper::format($data);
            return $data;
        }
        return [];
    }
}