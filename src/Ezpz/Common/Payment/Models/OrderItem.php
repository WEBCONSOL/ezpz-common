<?php

namespace Ezpz\Common\Payment\Models;

use Ezpz\Common\Helper\Type;

class OrderItem
{
    private $product_id = 0;
    private $product_name = '';
    private $product_desc = '';
    private $quantity = 0;
    private $price = 0;
    private $product_mode = 0;
    private $currency_code = '';
    private $discount = 0;
    private $tax = 0;

    public function __construct($orderItem)
    {
        if (!empty($orderItem) && (sizeof($orderItem) > 0))  {
            $this->product_id = isset($orderItem['id']) ? $orderItem['id'] : 0;
            $this->product_name = isset($orderItem['name']) ? $orderItem['name'] : '';
            $this->product_desc = isset($orderItem['name']) ? $orderItem['name'] : '';
            $this->quantity = isset($orderItem['quantity']) ? $orderItem['quantity'] : 0;
            $this->price = isset($orderItem['price']) ? $orderItem['price'] : 0;
            $this->product_mode = isset($orderItem['product_mode']) ? $orderItem['product_mode'] : '';
            if (!empty($orderItem['currency']) && isset($orderItem['currency']))  {
                if (!empty($orderItem['currency']['params']) && isset($orderItem['currency']['params']))  {
                    $this->currency_code = isset($orderItem['currency']['params']['code_3']) ? $orderItem['currency']['params']['code_3'] : '';
                }
            }
            if (!empty($orderItem['offer']) && isset($orderItem['offer']))  {
                $offer_type = $orderItem['offer']['offer_type'] ;
                $params     = $orderItem['offer']['params'];
                if (!empty($params) && isset($params)) {
                    if($offer_type === Type::$OFFER['discount']) {
                        if(isset($params['total_off'])) {
                            $this->discount = $params['total_off'];
                        }
                    } else if($offer_type === Type::$OFFER['bundle']) {
                        if (isset($params['product_1_id']) && isset($params['product_1_off']) && ($this->product_id == $params['product_1_id'])) {
                            $this->discount = $params['product_1_off'] ;
                        } else if (isset($params['product_2_id']) && isset($params['product_2_off']) && ($this->product_id == $params['product_2_id'])) {
                            $this->discount = $params['product_2_off'] ;
                        }
                    } else if($offer_type === Type::$OFFER['crosssell']) {
                        if (isset($params['product_2_id']) && isset($params['product_2_off'])) {
                            $this->discount = $params['product_2_off'] ;
                        }
                    } else if($offer_type === Type::$OFFER['upsell']) {
                        if (isset($params['product_2_id']) && isset($params['product_2_off'])) {
                            $this->discount = $params['product_2_off'] ;
                        }
                    }
                    $this->price = $this->price - $this->discount ;
                }
            }
            if (!empty($orderItem['taxes']) && isset($orderItem['taxes']) && (sizeof($orderItem['taxes']) > 0))  {
                $tax_rate = 0.0;
                foreach($orderItem['taxes'] as $i => $tax) {
                    $tax_rate += $tax['tax_rate'];
                }
                $this->tax = ($this->price) * ($tax_rate / 100);
            }
        }
    }
    public function getProductId() {return $this->product_id;}
    public function getProductName() {return $this->product_name;}
    public function getProductDescription() {return $this->product_desc;}
    public function getQuantity() {return $this->quantity;}
    public function getPrice() {return $this->price;}
    public function getProductMode() {return $this->product_mode;}
    public function getCurrencyCode() {return $this->currency_code;}
    public function getDiscount() {return $this->discount;}
    public function getTax() {return $this->tax;}
    public function setProductName($productName) { $this->product_name = $productName; }
    public function setProductDescription($productDesc) { $this->product_desc = $productDesc; }
    public function setQuantity($quantity) { $this->quantity = $quantity; }
    public function setPrice($price) { $this->price = $price; }
    public function setCurrencyCode($currencyCode) { $this->currency_code = $currencyCode; }
    public function setDiscount($discount) { $this->tax = $discount; }
    public function setTax($tax) { $this->tax = $tax; }
}