<?php

namespace Ezpz\Common\Payment\Services;

use Payment\Models\OrderItems;
use Payment\Models\CreditCard;
use Payment\Helpers\JSONObject;

interface PaymentServiceInterface
{
    public function __construct();
    public function getId();
    public function getName();

    public function getApiKey();

    public function setOrderId($orderId);
    public function setStoreId($storeId);

    public function setCheckoutType($type);
    public function setPaymentType($type);

    public function loadApiContext(JSONObject $paymentApiKey);
    public function createCreditCard(CreditCard $cardModel);

    public function pay(CreditCard $cardModel, OrderItems $orderItems);
}
