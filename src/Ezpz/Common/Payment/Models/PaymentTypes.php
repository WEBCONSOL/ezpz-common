<?php

namespace Ezpz\Common\Payment\Models;

class PaymentTypes
{
    const CREDIT_CARD = 'credit_card';
    private static $TYPES = array(
        'credit_card'
    );
    public static function has($var) {
        return in_array($var, self::$TYPES);
    }
    public static function getList() {
        return self::$TYPES;
    }
}