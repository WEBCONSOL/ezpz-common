<?php

namespace Ezpz\Common\ApiGateway;

use Slim\Container;
use Slim\Http;
use WC\Utilities\CustomResponse;

class CustomErrorHandler
{
    private static $debug = false;

    public function __invoke(Container $cnt)
    {
        return function (Http\Request $request, Http\Response $response, \Exception $exception) use ($cnt)
        {
            CustomResponse::setDebug(self::$debug);
            $resp = CustomResponse::getOutputFormattedAsArray(null, 500, self::$debug?trim(strip_tags($exception->getMessage())):null, false);
            return $response->withJson($resp);
        };
    }

    public static function setDebug(bool $debug) {self::$debug = $debug;}
}