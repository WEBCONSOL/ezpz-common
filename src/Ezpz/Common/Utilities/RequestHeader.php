<?php

namespace Ezpz\Common\Utilities;

class RequestHeader
{
    const REQUEST_PARAM_CONTENT_TYPE = "content_type";
    const REQUEST_PARAM_CONTENT_TYPE_ID = "contenttypeid";
    const REQUEST_PARAM_CONTENT_TYPE_ALIAS = "contenttypealias";
    const CONTENT_TYPE = "Content-Type";
    const ACCEPT = "Accept";
    const ACCEPT_ENCODING = "Accept-Encoding";
    const CRSFTOKEN = "csrftoken";
    const PAGE = "page";
    const NUM_PER_PAGE = "numPerPage";
    const NUM_ROWS = "numRows";
    const CLIENT_ID = "client_id";
    const CLIENT_SECRET = "client_secret";
    const ACCESS_TOKEN = "access_token";
    const USER_AGENT = "User-Agent";
    const MODE = "mode";
    const FORM_HEADER_CONTENTTYPE = "application/x-www-form-urlencoded; charset=UTF-8";
    const JSON_HEADER_CONTENTTYPE = "application/json; charset=UTF-8";
    const MULTIPART_FORM_HEADER_CONTENTTYPE = "multipart/form-data";

    private static $headers = array(
        self::CONTENT_TYPE => "application/json",
        self::ACCEPT => "application/json",
        self::USER_AGENT => "Ezpizee/1.0",
        self::MODE => "site",
        self::ACCESS_TOKEN => "",
        self::PAGE => 1,
        self::NUM_PER_PAGE => 15,
        self::NUM_ROWS => 0
    );

    private static $configRequest = array(
        self::CONTENT_TYPE => "application/json",
        self::USER_AGENT => "Ezpizee/1.0"
    );

    private function __construct(){}

    public static function getStandard($key = null) {
        if ($key) {
            return isset(self::$headers[$key]) ? self::$headers[$key] : null;
        }
        return self::$headers;
    }

    public static function set($key, $value) {
        if (isset(self::$headers[$key])) {
            self::$headers[$key] = $value;
        }
    }

    public static function forConfigRequest(array $headers=null) {
        if ($headers !== null) {
            $configRequest = self::$configRequest;
            foreach ($headers as $k=>$v) {
                $configRequest[$k] = $v;
            }
            return $configRequest;
        }
        return self::$configRequest;
    }

    public static function reloadConfig() {
        return self::forConfigRequest(array('reload'=>true));
    }
}