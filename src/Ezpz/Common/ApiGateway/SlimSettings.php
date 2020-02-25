<?php

namespace Ezpz\Common\ApiGateway;

class SlimSettings
{
    private $settings = array(
        'displayErrorDetails' => true,
        'determineRouteBeforeAppMiddleware' => false,
        'outputBuffering' => 'append'
    );

    public function append($key, $value) {
        $this->settings[$key] = $value;
    }

    public function getAsArray(): array
    {
        return array('settings' => $this->settings);
    }
}