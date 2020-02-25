<?php

namespace Ezpz\Common\ApiGateway;

use WC\Utilities\CustomResponse;

class Endpoints
{
    /**
     * @var array $data
     */
    private static $data = array();

    /**
     * void
     */
    private static function loadConfig()
    {
        if (empty(self::$data))
        {
            $file = PATH_COMMON_CONFIG . DS . 'endpoints.json';

            if (file_exists($file))
            {
                self::$data = json_decode(file_get_contents($file), true);
            }
            else
            {
                CustomResponse::render(500, "Endpoints configuration file is missing.");
            }
        }
    }

    public static function getDataAsArray(): array {self::loadConfig();return self::$data;}

    public static function __callStatic($name, $arguments)
    {
        $key = isset($arguments[0]) ? $arguments[0] : '';
        $uriParams = isset($arguments[1]) ? $arguments[1] : [];
        return self::uri($name, $key, $uriParams);
    }

    /**
     * @param string $root
     * @param string $key
     * @param array  $uriParams
     *
     * @return array
     */
    private static function uri(string $root, string $key, array $uriParams=array()): array
    {
        $output = array(Constants::METHODS['get']);
        self::loadConfig();

        if (isset(self::$data[$root]))
        {
            if ($key && isset(self::$data[$root][$key]))
            {
                $uri = self::$data[$root][$key];

                if (!empty($uriParams)) {
                    $output[1] = self::uriParams($uri, $uriParams);
                }
                else if (is_array($uri) && isset($uri['uri'])) {
                    if (isset($uri['method']) && isset(Constants::METHODS[$uri['method']])) {
                        $output[0] = Constants::METHODS[$uri['method']];
                    }
                    $output[1] = $uri['uri'];
                }
                else if (is_string($uri)) {
                    $output[1] = $uri;
                }
            }
            else
            {
                $output = self::$data[$root];
            }
        }

        return $output;
    }

    private static function uriParams(array $uri, array $uriParams): string
    {
        $parts = array();

        if (is_array($uri) && isset($uri['uri'])) {
            $parts = explode('/', $uri['uri']);
            if (isset($uri['method']) && isset(Constants::METHODS[$uri['method']])) {
                $output[0] = Constants::METHODS[$uri['method']];
            }
        }
        else if (is_string($uri)) {
            $parts = explode('/', $uri);
        }

        foreach ($parts as $key=>$val)
        {
            if (substr($val, 0, 1) === '{' && substr($val, strlen($val)-1, 1) === '}')
            {
                $val = str_replace(array('{', '}'), '', $val);

                if (isset($uriParams[$val]))
                {
                    $parts[$key] = $uriParams[$val];
                }
            }
        }

        return implode('/', $parts);
    }
}