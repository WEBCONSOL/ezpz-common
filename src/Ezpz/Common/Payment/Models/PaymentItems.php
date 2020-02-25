<?php

namespace Ezpz\Common\Payment\Models;

class PaymentItems implements \JsonSerializable
{
    private $data = [];
    private $customerId = 0;
    private $storeId = 0;
    private $shippingId = 0;
    private $paymentMethodId = 0;
    private $paymentType = "";
    private $paymentOauthToken = "";

    private $card = array();

    public function loadItem($item) {
        $this->data = $item;
        if (isset($item['customer_id'])) {
            $this->customerId = (int)$item['customer_id'];
        }
        if (isset($item['store_id'])) {
            $this->storeId = (int)$item['store_id'];
        }
        if (isset($item['shipping_id'])) {
            $this->shippingId = (int)$item['shipping_id'];
        }
        if (isset($item['payment_method_id'])) {
            $this->paymentMethodId = (int)$item['payment_method_id'];
        }
        if (isset($item['payment_type'])) {
            $this->paymentType = $item['payment_type'];
        }
        if (isset($item['payment_oauth_token'])) {
            $this->paymentOauthToken = $item['payment_oauth_token'];
        }
        if (!empty($item['card']) && isset($item['card']) && is_array($item['card'])) {
            $this->card = $item['card'] ;
        }
    }

    public function getCustomerId(): int {return $this->customerId;}
    public function getStoreId(): int {return $this->storeId;}
    public function getShippingId(): int {return $this->shippingId;}
    public function getPaymentType(): string {return $this->paymentType;}
    public function getCard(): array {return $this->card;}
    public function getPaymentMethodId(): int {return $this->paymentMethodId;}
    public function getPaymentOauthToken(): string {return $this->paymentOauthToken;}

    public function jsonSerialize(){return $this->data;}

    public function __toString(){return json_encode($this->jsonSerialize());}
}
