<?php

namespace Ezpz\Common\Payment\Services;

use \Ezpz\Common\Payment\Helpers\JSONObject;

interface PaymentApiKeyInterface {
    public function __construct(JSONObject &$object);
}