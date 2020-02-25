<?php

namespace Ezpz\Common\Payment\Services;

use Payment\Helpers\JSONObject;

interface PaymentApiKeyInterface {
    public function __construct(JSONObject &$object);
}