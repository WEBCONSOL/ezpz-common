<?php

namespace Ezpz\Common\Utilities;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\GuzzleException;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;
use WC\Utilities\StringUtil;

final class HttpClient
{
    private function __construct(){}

    public static function request($method, $url, array $options = array()): Response
    {
        $response = (new Client())->request($method, str_replace('local-auth.ezpizee.com', 'cache-proxy', $url), $options);
        if ($response === null) {
            $response = new Response();
        }

        return $response;
    }

    public static function internalRequest($method, $url, array $options = array()): Response
    {
        $request = new \WC\Utilities\Request();
        $token = $request->getHeaderParam(HEADER_ACCESS_TOKEN);
        $appName = $request->getHeaderParam(HEADER_APP_NAME);
        $storeId = $request->getHeaderParam(HEADER_STORE_ID);
        $userId = $request->getHeaderParam(HEADER_USER_ID);
        $userName = $request->getHeaderParam(HEADER_USER_NAME);
        $options['headers'] = isset($options['headers']) ? $options['headers'] : [];
        if ($token) {$options['headers'][HEADER_ACCESS_TOKEN] = $token;}
        if ($appName) {$options['headers'][HEADER_APP_NAME] = $appName;}
        if ($storeId) {$options['headers'][HEADER_STORE_ID] = $storeId;}
        if ($userId) {$options['headers'][HEADER_USER_ID] = $userId;}
        if ($userName) {$options['headers'][HEADER_USER_NAME] = $userName;}
        Logger::info('Internal '.strtoupper($method).' API Call '.$url);
        return self::request($method, $url, $options);
    }

    private static function terminate(GuzzleException $e)
    {
        $code = $e->getCode();
        $message = $e->getMessage();
        $output = CustomResponse::getOutputFormattedAsArray(null, $code, $message);
        header(HEADER_CONTENTTYPE.CONTENTTYPE_HEADER_JSON);
        http_response_code($code);
        die(json_encode($output));
    }
}