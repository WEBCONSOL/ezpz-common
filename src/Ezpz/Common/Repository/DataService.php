<?php

namespace Ezpz\Common\Repository;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use \Ezpz\Common\Helper\DigitalAsset;
use \Ezpz\Common\Helper\StoreHelper;
use \Ezpz\Common\Security\Token;
use WC\Utilities\EncodingUtil;
use WC\Utilities\Logger;
use WC\Utilities\DateTimeFormat;

class DataService
{
    /**
     * @var EntityManager $em
     */
    private $em;

    public function __construct(EntityManager &$em) {$this->em = $em;}

    public function getEm(): EntityManager {return $this->em;}

    public function getCommonCountryById(int $id): array {return $this->getData(DBTableConstants::COMMON_COUNTRIES, $id);}
    public function getCommonCountriesByIds(array $ids): array {return $this->getList(DBTableConstants::COMMON_COUNTRIES, $ids);}

    public function getCommonCurrencyById(int $id): array {return $this->getData(DBTableConstants::COMMON_CURRENCIES, $id);}
    public function getCommonCurrenciesByIds(array $ids): array {return $this->getList(DBTableConstants::COMMON_CURRENCIES, $ids);}

    public function getCommonPaymentServiceById(int $id): array {return $this->getData(DBTableConstants::COMMON_PAYMENTSERVICES, $id);}
    public function getCommonPaymentServicesByIds(array $ids): array {return $this->getList(DBTableConstants::COMMON_PAYMENTSERVICES, $ids);}

    public function getGlobalPropertyById(int $id): array {return $this->getData(DBTableConstants::ECOM_GLOBALPROPERTIES, $id);}
    public function getGlobalPropertiesByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_GLOBALPROPERTIES, $ids);}

    public function getContactById(int $id): array {return $this->getData(DBTableConstants::CONTACTS, $id);}
    public function getContactsByIds(array $ids): array {return $this->getList(DBTableConstants::CONTACTS, $ids);}

    public function getDAAssetById(int $id): array {return $this->getData(DBTableConstants::DA_ASSETS, $id);}
    public function getDAAssetsByIds(array $ids): array {return $this->getList(DBTableConstants::DA_ASSETS, $ids);}

    public function getDAAssetFileById(int $id): array {return $this->getData(DBTableConstants::DA_ASSETS_FILES, $id);}
    public function getDAAssetFilesByIds(array $ids): array {return $this->getList(DBTableConstants::DA_ASSETS_FILES, $ids);}

    public function getCategoryById(int $id): array {return $this->getData(DBTableConstants::ECOM_CATEGORIES, $id);}
    public function getCategoriesByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_CATEGORIES, $ids);}

    public function getCustomerById(int $id): array {return $this->getData(DBTableConstants::DA_ASSETS_FILES, $id);}
    public function getCustomersByIds(array $ids): array {return $this->getList(DBTableConstants::DA_ASSETS_FILES, $ids);}

    public function getManufacturerById(int $id): array {return $this->getData(DBTableConstants::ECOM_MANUFACTURERS, $id);}
    public function getManufacturersByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_MANUFACTURERS, $ids);}

    public function getOfferById(int $id): array {return $this->getData(DBTableConstants::ECOM_OFFERS, $id);}
    public function getOffersByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_OFFERS, $ids);}

    public function getPaymentMethodById(int $id): array {return $this->getData(DBTableConstants::ECOM_PAYMENTMETHODS, $id);}
    public function getPaymentMethodsByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_PAYMENTMETHODS, $ids);}

    public function getProductById(int $id): array {return $this->getData(DBTableConstants::ECOM_PRODUCTS, $id);}
    public function getProductsByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_PRODUCTS, $ids);}

    public function getProductAttrById(int $id): array {return $this->getData(DBTableConstants::ECOM_PRODUCT_ATTRS, $id);}
    public function getProductAttrsByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_PRODUCT_ATTRS, $ids);}

    public function getProductTypeById(int $id): array {return $this->getData(DBTableConstants::ECOM_PRODUCT_TYPES, $id);}
    public function getProductTypesById(array $ids): array {return $this->getList(DBTableConstants::ECOM_PRODUCT_TYPES, $ids);}

    public function getCartById(int $id): array {return $this->getData(DBTableConstants::ECOM_CARTS, $id);}
    public function cartExist(int $id): bool {return !empty($this->getData(DBTableConstants::ECOM_CARTS, $id));}

    public function getCouponById(int $id): array {return $this->getData(DBTableConstants::ECOM_COUPONS, $id);}
    public function getCouponsByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_COUPONS, $ids);}

    public function getOrderById(int $id): array {return $this->getData(DBTableConstants::ECOM_ORDERS, $id);}
    public function getOrdersByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_ORDERS, $ids);}
    public function orderExist(int $id): bool {return !empty($this->getData(DBTableConstants::ECOM_ORDERS, $id));}

    public function getShippingMethodById(int $id): array {return $this->getData(DBTableConstants::ECOM_SHIPPINGMETHODS, $id);}
    public function getShippingMethodsByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_SHIPPINGMETHODS, $ids);}

    public function getStoreById(int $id): array {return $this->getData(DBTableConstants::ECOM_STORES, $id);}
    public function getStoresByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_STORES, $ids);}

    public function getTaxById(int $id): array {return $this->getData(DBTableConstants::ECOM_TAXES, $id);}
    public function getTaxesByIds(array $ids): array {return $this->getList(DBTableConstants::ECOM_TAXES, $ids);}

    public function getProductInfoForCart(array $ids): array {
        $sql = 'SELECT id, name, description, price, product_mode, params '.
            'FROM '.DBTableConstants::ECOM_PRODUCTS.' WHERE id IN('.implode(',', $ids).')';
        return $this->fetchRows($sql);
    }

    public function getStoreIdByCartId(int $cartId): int {
        $sql = 'SELECT o.store_id '.
            'FROM '.DBTableConstants::ECOM_CARTS.' c LEFT JOIN '.DBTableConstants::ECOM_ORDERS.' o ON o.id=c.order_id '.
            ' WHERE c.id='.$cartId;
        $row = $this->fetchRow($sql);
        return empty($row) ? 0 : (int)$row['store_id'];
    }

    public function getStoreByCartId(int $cartId): array {
        if ($cartId <= 0) {return [];}
        $sql = 'SELECT s.* '.
            'FROM '.DBTableConstants::ECOM_CARTS.' c LEFT JOIN '.DBTableConstants::ECOM_ORDERS.' o ON o.id=c.order_id '.
            'LEFT JOIN '.DBTableConstants::ECOM_STORES.' s ON s.id=o.store_id '.
            'WHERE c.id='.$cartId;
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        StoreHelper::properties($row, $this);
        $row['payment_methods'] = $this->getStorePaymentMethods((int)$row['id']);
        $row['empty_payment_method'] = ['id'=>'0', 'paytype'=>'na'];
        return $row;
    }

    public function getStoreByOrderId(int $orderId): array {
        if ($orderId <= 0) {return [];}
        $sql = 'SELECT s.* '.
            'FROM '.DBTableConstants::ECOM_ORDERS.' o '.
            'LEFT JOIN '.DBTableConstants::ECOM_STORES.' s ON s.id=o.store_id '.
            'WHERE c.id='.$orderId;
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        StoreHelper::properties($row, $this);
        $row['payment_methods'] = $this->getStorePaymentMethods((int)$row['id']);
        $row['empty_payment_method'] = ['id'=>'0', 'paytype'=>'na'];
        return $row;
    }

    public function getOrderByCartId(int $cartId): array {
        if ($cartId <= 0) {return [];}
        $sql = 'SELECT o.* '.
            'FROM '.DBTableConstants::ECOM_CARTS.' c LEFT JOIN '.DBTableConstants::ECOM_ORDERS.' o ON o.id=c.order_id '.
            'WHERE c.id='.$cartId;
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        return $row;
    }

    public function getOrderByOrderId(int $orderId): array {
        if ($orderId <= 0) {return [];}
        $sql = 'SELECT o.* '.
            'FROM '.DBTableConstants::ECOM_ORDERS.' o '.
            'WHERE o.id='.$orderId;
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        return $row;
    }

    public function getCustomerByCartId(int $cartId): array {
        if ($cartId <= 0) {return [];}
        $sql = 'SELECT * '.
            'FROM '.DBTableConstants::ECOM_CARTS.' a '.
            'LEFT JOIN '.DBTableConstants::ECOM_ORDERS.' b ON b.id=a.order_id '.
            'LEFT JOIN '.DBTableConstants::ECOM_CUSTOMERS.' c ON c.id=b.customer_id '.
            'WHERE a.id="'.$cartId.'"';
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        return $row;
    }

    public function getCustomerByOrderId(int $orderId): array {
        if ($orderId <= 0) {return [];}
        $sql = 'SELECT * '.
            'FROM '.DBTableConstants::ECOM_ORDERS.' a '.
            'LEFT JOIN '.DBTableConstants::ECOM_CUSTOMERS.' b ON b.id=a.customer_id '.
            'WHERE a.id="'.$orderId.'"';
        $row = $this->fetchRow($sql);
        if (empty($row)) {return [];}
        return $row;
    }

    public function getStorePaymentMethods(int $storeId): array {
        $sql = 'SELECT * FROM '.DBTableConstants::ECOM_PAYMENTMETHODS.' WHERE store_id='.$this->quote($storeId);
        return $this->fetchRows($sql);
    }

    public function getPaymentMethodsByCartId(int $cartId) {
        $sql = 'SELECT * FROM '.DBTableConstants::ECOM_PAYMENTMETHODS.' pm WHERE store_id=('.'SELECT s.id '.
            'FROM '.DBTableConstants::ECOM_CARTS.' c LEFT JOIN '.DBTableConstants::ECOM_ORDERS.' o ON o.id=c.order_id '.
            'LEFT JOIN '.DBTableConstants::ECOM_STORES.' s ON s.id=o.store_id '.
            'WHERE c.id='.$cartId.')';
        return $this->fetchRows($sql);
    }

    public function getStoreTaxes(array $storeIds): array {
        $sql = 'SELECT t.*,st.store_id '.
            'FROM '.DBTableConstants::ECOM_STORE_TAXES.' st LEFT JOIN '.DBTableConstants::ECOM_TAXES.' t ON t.id=st.tax_id '.
            ' WHERE st.store_id IN('.implode(',', $storeIds).')';
        return $this->fetchRows($sql);
    }

    public function getAccessToken(string $token, string $appName=''): array {
        $sql = 'SELECT * FROM '.DBTableConstants::OAUTH_ACCESS_TOKENS. ' WHERE access_token="'.$token.'"'.
            ($appName ? 'AND hashed_app_name="'.md5($appName).'"':'');
        return $this->fetchRow($sql);
    }

    public function getAccessTokenByClientId(string $token, string $appName='', int $userId=0): array {
        $sql = 'SELECT * FROM '.DBTableConstants::OAUTH_ACCESS_TOKENS. ' WHERE client_id="'.$token.'"'.
            ($appName ? ' AND hashed_app_name="'.md5($appName).'"':'').
            ($userId > 0 ? ' AND user_id="'.$userId.'"':'');
        return $this->fetchRow($sql);
    }

    public function getOauthPublicKeys(Token $jwtToken): array {
        return $this->getDataFromTable(
            DBTableConstants::OAUTH_PUBLIC_KEYS,
            'client_id="'.$jwtToken->client['client_id'].'" AND hashed_app_name="'.md5($jwtToken->appName).'"',
            true
        );
    }

    public function createOrder(int $storeId, string $channel, string $employee=''): int {
        $columns = $this->getTableColumns(DBTableConstants::ECOM_ORDERS);
        $fields = [];
        $values = [];
        foreach ($columns as $column) {
            if ($column !== 'id') {
                $fields[] = $this->quoteName($column);
                if ($column === 'params') {
                    $values[] = $this->quote('');
                }
                else if ($column === 'store_id') {
                    $values[] = $this->quote($storeId);
                }
                else if ($column === 'employee') {
                    $values[] = $this->quote($employee);
                }
                else if ($column === 'channel') {
                    $values[] = $this->quote($channel);
                }
                else if ($column === 'created_on' || $column === 'modified_on') {
                    $values[] = $this->quote(DateTimeFormat::getFormatUnix());
                }
                else {
                    $values[] = $this->quote('0');
                }
            }
        }
        $sql = 'INSERT INTO '.DBTableConstants::ECOM_ORDERS.'('.implode(',',$fields).') VALUES('.implode(',',$values).')';
        $this->executeQuery($sql);
        return $this->em->getConnection()->lastInsertId();
    }

    public function insertOrderCoupon(int $order_id, int $coupon_id, float $cost) {
        $columns = $this->getTableColumns(DBTableConstants::ECOM_ORDER_COUPONS);
        $fields = [];
        $values = [];
        foreach ($columns as $j=>$column) {
            $fields[] = $this->quoteName($column);
            if ($column === 'order_id') {
                $values[] = $order_id;
            }
            else if ($column === 'coupon_id') {
                $values[] = $coupon_id;
            }
            else if ($column === 'cost') {
                $values[] = $cost;
            }
            else if ($column === 'created_on') {
                $values[] = $this->quote(DateTimeFormat::getFormatUnix());
            }
        }
        $sql = 'INSERT INTO '.DBTableConstants::ECOM_ORDER_COUPONS.'('.implode(',',$fields).') VALUES ('.implode(',',$values).')';
        $this->executeQuery($sql);
    }

    public function insertOrderHistory(int $id, string $state, string $note) {
        $columns = $this->getTableColumns(DBTableConstants::ECOM_ORDER_HISTORY);
        $fields = [];
        $values = [];
        foreach ($columns as $column) {
            $fields[] = $this->quoteName($column);
            if ($column === 'order_id') {
                $values[] = $id;
            }
            else if ($column === 'sequence_num') {
                $values[] = $this->getMaxSequenceById($id);
            }
            else if ($column === 'state') {
                $values[] = $this->quote($state);
            }
            else if ($column === 'notes') {
                $values[] = $this->quote($note);
            }
            else if ($column === 'history_datetime') {
                $values[] = $this->quote(DateTimeFormat::getFormatUnix());
            }
        }
        $sql = 'INSERT INTO '.DBTableConstants::ECOM_ORDER_HISTORY.'('.implode(',',$fields).') VALUES('.implode(',',$values).')';
        $this->executeQuery($sql);
    }

    public function insertInvoice(int $order_id, int $store_id, float $total, $params) : int {

        // die(gettype($params));

        if(gettype($params) == 'array') {
            $params = $this->quote((string)json_encode($params));
        } else if(gettype($params) == 'object') {
            $params = $this->quote(json_encode($params));
        }

        $created_on = $this->quote(DateTimeFormat::getFormatUnix());
        $sql = 'INSERT INTO '.DBTableConstants::ECOM_INVOICES.'(order_id, store_id, total_amount, params, created_on) VALUES('.$order_id.','.$store_id.','.$total.','.$params.','.$created_on.')';
        $this->executeQuery($sql);
        return $this->em->getConnection()->lastInsertId();
    }

    public function updateData(string $tb, array $fieldValuePairs, string $condition, bool $quote=true): bool {
        $stm = [];
        foreach ($fieldValuePairs as $field=>$value) {
            $stm[] = $field.'='.($quote?$this->quote($value):$value);
        }
        return $this->executeQuery('UPDATE '.$tb.' SET '.implode(',', $stm).' WHERE '.$condition);
    }

    public function deleteFromTable(string $tb, string $condition): bool {
        try {
            $sql = 'DELETE FROM '.$tb.' WHERE '.$condition;
            $exec = $this->em->getConnection()->exec($sql);
            if ($exec) {return true;}
        }
        catch (DBALException $e) {
            Logger::error('sql. '.$e->getMessage());
        }
        return false;
    }

    public function getDataFromTable(string $tb, string $condition, bool $singleRow=false): array {
        if ($singleRow) {
            return $this->fetchRow('SELECT * FROM '.$tb.($condition ? ' WHERE '.$condition : ''));
        }
        else {
            return $this->fetchRows('SELECT * FROM '.$tb.($condition ? ' WHERE '.$condition : ''));
        }
    }

    public function deleteProductValues(array $ids) {
        try {
            $sql = 'DELETE FROM '.DBTableConstants::ECOM_PRODUCT_VALUES.' WHERE product_id IN('.implode(',', $ids).')';
            $this->em->getConnection()->exec($sql);
        }
        catch (DBALException $e) {
            Logger::error('sql. '.$e->getMessage());
        }
    }

    public function getAssetList(array $file_ids): array {
        $results = [];
        if (sizeof($file_ids) > 0 ) {
            $sql = 'SELECT a.id,a.title,a.type,a.asset_fs_id,b.path,b.attrs ' .
                'FROM ' . DBTableConstants::DA_ASSETS . ' a LEFT JOIN ' . DBTableConstants::DA_ASSETS_FILES . ' b ON b.id=a.asset_fs_id ' .
                'WHERE a.id IN("' . implode('","', $file_ids) . '")';
            $results = $this->fetchRows($sql);
            if (!empty($results)) {
                foreach ($results as $i => $item) {
                    if (null !== $results[$i]['path']) {
                        $results[$i]['path'] = DigitalAsset::toWebPath($results[$i]['path']);
                    }
                }
            }
        }
        return empty($results) ? [] : $results;
    }

    public function getMaxSequenceById(int $id): int {
        $sql = 'SELECT MAX(sequence_num) AS sequence_num FROM '.DBTableConstants::ECOM_ORDER_HISTORY.' WHERE order_id='.$id;
        $result = $this->em->getConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
        if (!empty($result)) {
            $sequence_num = (int)$result['sequence_num'] + 1 ;
        } else {
            $sequence_num = 1 ;
        }
        return $sequence_num;
    }

    public function getCountryByGlobalPropertyId(int $id): array {
        $globalProperties = $this->getGlobalPropertyById($id);
        if (!empty($globalProperties)) {
            if (isset($globalProperties['params']) && is_array($globalProperties['params'])) {
                return ['id'=>$globalProperties['id'], 'name'=>$globalProperties['params']['name'], 'params'=>$globalProperties['params']];
            }
            $item = $this->getCommonCountryById((int)$globalProperties['config_value']);
            if (!empty($item)) {
                return ['id' => $id, 'name' => $item['name'], 'params' => $item];
            }
        }
        return [];
    }

    public function getCurrencyByGlobalPropertyId(int $id): array {
        $globalProperties = $this->getGlobalPropertyById($id);
        if (!empty($globalProperties)) {
            if (isset($globalProperties['params']) && is_array($globalProperties['params'])) {
                return ['id'=>$globalProperties['id'], 'name'=>$globalProperties['params']['name'], 'params'=>$globalProperties['params']];
            }
            $item = $this->getCommonCurrencyById((int)$globalProperties['config_value']);
            if (!empty($item)) {
                return ['id' => $id, 'name' => $item['name'], 'params' => $item];
            }
        }
        return [];
    }

    public function getTableColumns(string $tableName): array {
        $rows = $this->fetchRows('DESCRIBE ' . $this->quoteName($tableName));
        if (!empty($rows)) {
            $newRows = array();
            foreach ($rows as $i=>$row) {
                $newRows[] = $row['Field'];
            }
            return $newRows;
        }
        return [];
    }

    public function quoteName(string $str): string {return $this->em->getConnection()->quoteIdentifier($str);}

    public function quote(string $str): string {return $this->em->getConnection()->quote($str);}

    public final function fetchRow(string $query): array {
        try {
            $row = $this->em->getConnection()->executeQuery($query)->fetch(\PDO::FETCH_ASSOC);
            if (!empty($row)) {$this->decode($row);}
        }
        catch (DBALException $e) {
            Logger::error('sql. '.$e->getMessage());
            $row = [];
        }
        return is_array($row) ? $row : [];
    }

    public final function fetchRows(string $query): array {
        try {
            $rows = $this->em->getConnection()->executeQuery($query)->fetchAll(\PDO::FETCH_ASSOC);
            if (!empty($rows)) {$this->decode($rows);}
        }
        catch (DBALException $e) {
            Logger::error('sql. '.$e->getMessage());
            $rows = [];
        }
        return is_array($rows) ? $rows : [];
    }

    public final function executeQuery(string $query): bool {
        $done = false;
        try {
            $n = $this->em->getConnection()->exec($query);
            $done = $n > 0;
        }
        catch (DBALException $e) {
            Logger::error('sql. '.$e->getMessage());
        }
        return $done;
    }

    public function decode(array &$arr) {
        foreach ($arr as $i=>$v) {
            if (is_array($v)) {
                $this->decode($arr[$i]);
            }
            else if (!is_numeric($v) && EncodingUtil::isValidJSON($v)) {
                $arr[$i] = json_decode($v, true);
                $this->decode($arr[$i]);
            }
        }
    }

    public function getAppIdentityForKafka(string $accessToken, string $hashedAppName): array {
        $sql = 'SELECT a.client_id,a.app_name'.
            ' FROM '.DBTableConstants::OAUTH_ACCESS_TOKENS.' a LEFT JOIN '.DBTableConstants::OAUTH_USERS.' b ON b.id=a.user_id'.
            ' WHERE a.access_token="'.$accessToken.'" AND hashed_app_name="'.$hashedAppName.'"';
        return $this->fetchRow($sql);
    }

    private function getData(string $tb, int $id): array {return $this->fetchRow('SELECT * FROM '.$tb.' WHERE id="'.$id.'"');}

    private function getList(string $tb, array $ids): array {
        return $this->fetchRows('SELECT * FROM '.$tb.(sizeof($ids)?' WHERE id IN("'.implode('","', $ids).'")':''));
    }

    public function getTotalAmount(int $order_id): array {
        $amount = ['subtotal'=>0.0,'discount'=>0.0, 'tax'=>0.0, 'coupon'=>0.0, 'total'=>0.0];
        $results = $this->getDataFromTable(DBTableConstants::ECOM_ORDER_ITEMS, 'order_id='.$order_id);
        if (!empty($results)) {
            $coupon_flag = true ;
            foreach ($results as $i => $result) {
                $subtotal = $result['product_price']*$result['quantity'];
                $discount = $result['discount']*$result['quantity'];
                if (!empty($result['product_taxes']) && sizeof($result['product_taxes'])) {
                    $tax_rate = 0.0;
                    foreach ($result['product_taxes'] as $j => $tax) {
                        $tax_rate+=$tax['tax_rate'];
                    }
                    // tax
                    $amount['tax']+=($subtotal-$discount)*($tax_rate/100);
                }
                // subtotal
                $amount['subtotal'] += $subtotal ;
                // discount
                $amount['discount'] += $discount ;
                if($coupon_flag) {
                    // coupon
                    $amount['coupon'] = $this->getCouponCost($order_id) ;
                    if($amount['coupon'] > 0.0) {
                        // tax
                        $amount['tax']-=$amount['coupon']*($tax_rate/100);
                    }
                    $coupon_flag = false ;
                }
            }
        }
        unset($sql);
        unset($results);
        // total
        $amount['total'] = $amount['subtotal'] - $amount['discount'] + $amount['tax'] - $amount['coupon'];
        // die('total('.$amount['total'].')=subtotal('.$amount['subtotal'].')-discount('.$amount['discount'].')+tax('.$amount['tax'].')-coupon('.$amount['coupon'].")");
        return $amount;
    }

    public function getCouponCost(int $order_id) : float {
        $coupon_cost = 0.0 ;
        $sql = 'SELECT SUM(cost) AS cost FROM '.DBTableConstants::ECOM_ORDER_COUPONS.' WHERE order_id='.$order_id;
        $result = $this->fetchRow($sql);
        if (!empty($result) && isset($result)) {
            $coupon_cost = (float)$result['cost'] ;
        }
        unset($sql);
        unset($result);
        return $coupon_cost;
    }

    public function getShippingCost($shippingMethodId=0) {
        if (!$shippingMethodId) {
            // $shippingMethodId = Request::getHeaderParam('shipping_method_id', 0);
        }
        if ($shippingMethodId) {
            $query = 'SELECT shipping_cost,tax_rate FROM ' . DBTableConstants::ECOM_SHIPPINGMETHODS . ' WHERE id=' . $this->dbResource->quote($shippingMethodId);
            $row = $this->dbResource->loadAssoc($query);
            if ($row && isset($row['shipping_cost'])) {
                $shippingCost = $row['shipping_cost'];
                $taxRate = $row['tax_rate'];
                $this->cost = $shippingCost + (($shippingCost*$taxRate)/100);
            }
        }
    }
}