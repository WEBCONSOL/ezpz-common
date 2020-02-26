<?php

namespace Ezpz\Common\Repository;

use WC\Models\ListModel;
use Ezpz\Common\Repository\DBTableConstants;

class ProductService
{
    /**
     * @var DataService $dataService
     */
    private $dataService;

    public function __construct(DataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * @param int $id
     *
     * @return ListModel
     */
    public function getProductDataByID(int $id): ListModel {
        $result = $this->dataService->fetchRow('SELECT * FROM ' . DBTableConstants::ECOM_PRODUCTS . ' WHERE id="'.$id.'"');
        if (!empty($result)) {
            return new ListModel($result);
        }
        return new ListModel([]);
    }

    /**
     * @param array $ids
     *
     * @return ListModel
     */
    public function getProductsDataByIDs(array $ids): ListModel {
        $result = new ListModel([]);
        $row = $this->dataService->fetchRows('SELECT * FROM ' . DBTableConstants::ECOM_PRODUCTS . ' WHERE id IN("'.implode('","', $ids).'")');
        if (!empty($row)) {
            foreach ($row as $item) {
                $result->set($item['id'], new ListModel($item));
            }
        }
        return $result;
    }
}