<?php

namespace Ezpz\Common\Payment\Helpers;

use WC\Utilities\EncodingUtil;

class JSONObject
{
    protected $output = null;

    function __construct($var = null)
    {
        if (EncodingUtil::isValidJSON($var)) {
            $this->output = json_decode($var);
        } else {
            $this->output = $var;
        }
    }

    public final function getOutput()
    {
        return $this->output;
    }

    public final static function encode($object)
    {
        return is_object($object) || is_array($object) ? json_encode($object) : null;
    }

    public final static function decode($str, $toArray = false)
    {
        return EncodingUtil::isValidJSON($str) ? json_decode($str, $toArray) : null;
    }
}