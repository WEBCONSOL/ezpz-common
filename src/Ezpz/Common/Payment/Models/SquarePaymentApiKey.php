<?php

namespace Ezpz\Common\Payment\Models;

use \Ezpz\Common\Payment\Helpers\JSONObject;
use \Ezpz\Common\Payment\Services\PaymentApiKeyInterface;

class SquarePaymentApiKey implements PaymentApiKeyInterface
{
    private $applicationId;
    private $locationId;
    private $accessToken;
    private $paymentType = 'credit_card';

    public function __construct(JSONObject &$object)
    {
        $output = $object->getOutput();
        if (isset($output['application_id'])) {
            $this->applicationId = $output['application_id'];
        }
        if (isset($output['location_id'])) {
            $this->locationId = $output['location_id'];
        }
        if (isset($output['access_token'])) {
            $this->accessToken = $output['access_token'];
        }
        if (isset($output['paytype'])) {
            $this->paymentType = $output['paytype'];
        }
    }

    public function getApplicationId() { return $this->applicationId; }
    public function getLocationId() { return $this->locationId; }
    public function getAccessToken() { return $this->accessToken; }
    public function getPaymentType() { return $this->paymentType; }
}