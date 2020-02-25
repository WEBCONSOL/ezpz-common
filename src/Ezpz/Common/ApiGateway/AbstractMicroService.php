<?php

namespace Ezpz\Common\ApiGateway;

abstract class AbstractMicroService
{
    abstract public function process(): \Slim\Http\Response;
}