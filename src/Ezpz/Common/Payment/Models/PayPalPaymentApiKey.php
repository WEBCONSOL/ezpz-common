<?php

namespace Ezpz\Common\Payment\Models;

use \Ezpz\Common\Payment\Helpers\JSONObject;
use \Ezpz\Common\Payment\Services\PaymentApiKeyInterface;

class PayPalPaymentApiKey implements PaymentApiKeyInterface
{
    private $clientId;
    private $clientSecret;
    private $paymentType = 'credit_card';

    public function __construct(JSONObject &$object)
    {
        $output = $object->getOutput();
        if (isset($output['client_id'])) {
            $this->clientId = $output['client_id'];
        }
        if (isset($output['client_secret'])) {
            $this->clientSecret = $output['client_secret'] ;
        }
        if (isset($output['paytype'])) {
            $this->paymentType = $output['paytype'];
        }
    }

    public function getClientId() { return $this->clientId; }
    public function getClientSecret() { return $this->clientSecret; }
    public function getPaymentType() { return $this->paymentType; }
}