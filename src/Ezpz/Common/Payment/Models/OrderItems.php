<?php

namespace Ezpz\Common\Payment\Models;

class OrderItems
{
    private $orderId = 0;

    private $subtotal = 0.0;
    private $discount = 0.0;
    private $tax = 0.0;
    private $coupon = 0.0;
    private $total = 0.0;

    private $orderItems = [] ;
    public function __construct(array $orderItems)
    {
        $this->orderItems = array();
        if (!empty($orderItems) && is_array($orderItems) && sizeof($orderItems) > 0) {
            // die(json_encode($orderItems['params']));
            if (!empty($orderItems['subtotal']) && isset($orderItems['subtotal'])) {
                $this->subtotal = $orderItems['subtotal'];
            }
            if (!empty($orderItems['discount']) && isset($orderItems['discount'])) {
                $this->discount = $orderItems['discount'];
            }
            if (!empty($orderItems['tax']) && isset($orderItems['tax'])) {
                $this->tax = $orderItems['tax'];
            }
            if (!empty($orderItems['coupon']) && isset($orderItems['coupon'])) {
                $this->coupon = $orderItems['coupon'];
            }
            if (!empty($orderItems['total']) && isset($orderItems['total'])) {
                $this->total = $orderItems['total'];
            }
            if (!empty($orderItems['params']) && is_array($orderItems['params']) && sizeof($orderItems['params']) > 0) {
                foreach ($orderItems['params'] as $i => $item) {
                    $this->orderItems[] = new OrderItem($item);
                }
            }
        }
    }
    public function setOrderId($orderId) {$this->orderId = $orderId;}
    public function getOrderId():float {return $this->orderId;}
    public function setSubtotal(float $subtotal) {$this->subtotal = $subtotal;}
    public function getSubtotal():float {return $this->subtotal;}
    public function setDiscount(float $discount) {$this->discount = $discount;}
    public function getDiscount():float {return $this->discount;}
    public function setTax(float $tax) {$this->tax = $tax;}
    public function getTax():float {return $this->tax;}
    public function setCoupon(float $coupon) {$this->coupon = $coupon;}
    public function getCoupon():float {return $this->coupon;}
    public function setTotal(float $total) {$this->total = $total;}
    public function getTotal():float {return $this->total;}
    public function getItems() {return $this->orderItems;}
    public function isEmpty() { return sizeof($this->orderItems) <= 0; }
}
