<?php

namespace Ezpz\Common\Helper;

use Ezpz\Common\ApiGateway\Endpoints;
use Ezpz\Common\Repository\DataService;
use Ezpz\Common\Repository\DBTableConstants;
use Ezpz\Common\Utilities\HostNames;
use Ezpz\Common\Utilities\HttpClient;

final class CartHelper
{
    public static function format(
        int $cartId,
        int $orderId,
        array &$result,
        DataService $dataService,
        AbstractApiContextProcessor $cp
    )
    {
        $store = [];
        $order = [];
        $taxes = [];
        $exempt_taxes = [];
        $productLevelDiscount = 0;
        $orderLevelDiscount = 0;
        $orderLevelDiscountType = '';
        $currencySymbol = '';
        $subTotal = 0;
        $totalTaxes = 0;
        if ($cartId > 0) {
            $order = $dataService->getOrderByCartId($cartId);
            $store = $dataService->getStoreByCartId($cartId);
            $cp->setResponseElement('customer', $dataService->getCustomerByCartId($cartId));
        }
        else if ($orderId > 0) {
            $order = $dataService->getOrderByOrderId($cartId);
            $store = $dataService->getStoreByOrderId($cartId);
            $cp->setResponseElement('customer', $dataService->getCustomerByOrderId($orderId));
        }
        if (isset($store['taxes']) && is_array($store['taxes'])) {$taxes = $store['taxes'];}
        if (isset($order['exempt_taxes']) && is_array($order['exempt_taxes'])) {$exempt_taxes = $order['exempt_taxes'];}
        if (isset($order['discount'])) {$orderLevelDiscount = (float)$order['discount'];}
        if (isset($order['discount_type'])) {$orderLevelDiscountType = $order['discount_type'];}
        $cp->setResponseElement('store', $store);
        $cp->setResponseElement('order', $order);
        if (sizeof($taxes)) {
            foreach ($taxes as $i=>$tax) {
                if (in_array($tax['id'], $exempt_taxes)) {
                    $taxes[$i]['cost'] = 0;
                    $taxes[$i]['exempted'] = true;
                }
            }
        }

        if (isset($result['params'])) {$result = $result['params'];}
        if (!is_array($result)) {$result = json_decode(json_encode($result), true);}

        // get discount per product
        $ids = [];$pos = [];
        if (is_array($result)) {
            foreach ($result as $i=>$item) {
                if (!is_array($item)) {$item = json_decode(json_encode($item), true);}
                $pos[$item['id']] = $i;
                $ids[] = $item['id'];
                $result[$i]['totalPrice'] = (float)$item['price'] * (float)$item['quantity'];
                $subTotal = $subTotal + $result[$i]['totalPrice'];
                if (!$currencySymbol && isset($item['currency']) && isset($item['currency']['params']) && isset($item['currency']['params']['symbol'])) {
                    $currencySymbol = $item['currency']['params']['symbol'];
                }
            }

            if ($orderLevelDiscountType === 'percent') {
                $orderLevelDiscount = ($subTotal * $orderLevelDiscount) / 100;
            }

            if (!empty($ids)) {
                $query = 'SELECT product_id,exempt_taxes,discount,discount_type FROM '.DBTableConstants::ECOM_ORDER_ITEMS.' WHERE product_id IN("'.implode('","', $ids).'")';
                $rows = $dataService->fetchRows($query);
                if (!empty($rows)) {
                    foreach ($rows as $item) {
                        $result[$pos[$item['product_id']]]['discount'] = $item['discount'];
                        if ($item['discount_type'] === 'percent') {
                            $productLevelDiscount = $productLevelDiscount + (((float)$item['discount'] * (float)$item['product_price'])/100);
                        }
                        else if ($item['discount_type'] === 'amount') {
                            $productLevelDiscount = $productLevelDiscount + ((float)$item['discount']);
                        }

                        if (!isset($result[$pos[$item['product_id']]]['exempted'])) {$result[$pos[$item['product_id']]]['exempted'] = [];}
                        if (!is_array($item['exempt_taxes'])) {$item['exempt_taxes'] = [];}

                        foreach ($taxes as $j=>$tax) {
                            if (!in_array($tax['id'], $item['exempt_taxes'])) {
                                $totalPrice = (float)$result[$pos[$item['product_id']]]['totalPrice'];
                                if (!isset($tax['exempted'])) {
                                    if (!isset($tax['cost'])) {$taxes[$j]['cost'] = 0;}
                                    $taxes[$j]['cost'] = $taxes[$j]['cost'] + (($totalPrice * ((float)$taxes[$j]['tax_rate']))/100);
                                }
                                else {
                                    $result[$pos[$item['product_id']]]['exempted'][] = $tax['tax_code'];
                                }
                            }
                            else {
                                $result[$pos[$item['product_id']]]['exempted'][] = $tax['tax_code'];
                            }
                        }
                    }
                }
            }

            // format taxes
            foreach ($taxes as $i=>$tax) {
                if (isset($taxes[$i]['cost'])) {
                    $taxes[$i]['cost'] = number_format($taxes[$i]['cost'], 2);
                    $totalTaxes = $totalTaxes + $taxes[$i]['cost'];
                }
            }
        }

        $discount = $orderLevelDiscount + $productLevelDiscount;
        $total = ($subTotal + $totalTaxes) - $discount;

        $cp->setResponseElement('currencySymbol', $currencySymbol);
        $cp->setResponseElement('subTotal', number_format($subTotal, 2));
        $cp->setResponseElement('taxes', $taxes);
        $cp->setResponseElement('discount', number_format($discount, 2));
        $cp->setResponseElement('total', number_format($total, 2));
    }

    public static function getCartContentByCartIdRequest(int $cartId, array &$result, AbstractApiContextProcessor $cp) {
        if ($cartId > 0) {
            $endpoint = Endpoints::cart('itemsByCartId');
            $url = HostNames::getCart().str_replace('{cart_id}', $cartId, $endpoint[1]);
            $resp = HttpClient::internalRequest($endpoint[0], $url, []);
            if ($resp->getStatusCode() === 200) {
                $respBodyJson = json_decode($resp->getBody()->getContents(), true);
                foreach ($respBodyJson as $k=>$v) {
                    if ($k === 'data') {
                        $result = $v;
                    }
                    else {
                        $cp->setResponseElement($k, $v);
                    }
                }
            }
        }
    }

    public static function getCartContentByOrderIdRequest(int $orderId, array &$result, AbstractApiContextProcessor $cp) {
        if ($orderId > 0) {
            $endpoint = Endpoints::cart('itemsByOrderId');
            $url = HostNames::getCart().str_replace('{order_id}', $orderId, $endpoint[1]);
            $resp = HttpClient::internalRequest($endpoint[0], $url, []);
            if ($resp->getStatusCode() === 200) {
                $respBodyJson = json_decode($resp->getBody()->getContents(), true);
                foreach ($respBodyJson as $k=>$v) {
                    if ($k === 'data') {
                        $result = $v;
                    }
                    else {
                        $cp->setResponseElement($k, $v);
                    }
                }
            }
        }
    }
}