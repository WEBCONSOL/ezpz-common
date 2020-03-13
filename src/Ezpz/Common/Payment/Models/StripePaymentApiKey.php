<?php

namespace Ezpz\Common\Payment\Models;

use \Ezpz\Common\Payment\Helpers\JSONObject;
use \Ezpz\Common\Payment\Services\PaymentApiKeyInterface;

class StripePaymentApiKey implements PaymentApiKeyInterface
{
    private $publishableKey;
    private $secretKey;
    private $paymentType = 'credit_card';

    public function __construct(JSONObject &$object)
    {
        $output = $object->getOutput();
        if (isset($output['publishable_key'])) {
            $this->publishableKey = $output['publishable_key'];
        }
        if (isset($output['secret_key'])) {
            $this->secretKey = $output['secret_key'] ;
        }
        if (isset($output['paytype'])) {
            $this->paymentType = $output['paytype'];
        }
    }

    public function getPublishableKey() { return $this->publishableKey; }
    public function getSecretKey() { return $this->secretKey; }
    public function getPaymentType() { return $this->paymentType; }
}