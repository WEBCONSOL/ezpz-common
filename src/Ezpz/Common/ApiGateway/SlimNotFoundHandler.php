<?php

namespace Ezpz\Common\ApiGateway;

use Slim\Container;
use Slim\Http;

class SlimNotFoundHandler
{
    public function __invoke(Container $cnt) {
        return function (Http\Request $request, Http\Response $response) use ($cnt): Http\Response {
            return $response->withJson(json_decode(file_get_contents(PATH_COMMON_STATIC . DS . 'json' . DS . '404.json')));
        };
    }
}