<?php

namespace Ezpz\Common\ApiGateway;

use Slim\Container;
use Slim\Http;

class SlimNotAllowedHandler
{
    public function __invoke(Container $cnt) {
        return function (Http\Request $request, Http\Response $response, array $methods) use ($cnt) {
            return $response->withJson(array(
                'status' => false,
                'code' => 500,
                'message' => 'Method not allowed. Must be one of: '.implode(', ', $methods)
            ));
        };
    }
}