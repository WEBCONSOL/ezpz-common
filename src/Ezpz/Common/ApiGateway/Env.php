<?php

namespace Ezpz\Common\ApiGateway;

use Ezpz\Common\Utilities\HostNames;
use \WC\Models\ListModel;

class Env
{
    const ENVS = array('local', 'dev', 'qa', 'stage', 'prod');
    const ENV_HOST_SCHEMAS = array(
        'http://local', 'http://dev', 'http://qa', 'http://stage', 'http://prod',
        'https://local', 'https://dev', 'https://qa', 'https://stage', 'https://prod'
    );
    private static $data = null;

    public function __construct(ListModel $options)
    {
        self::$data = new ListModel(array());

        if ($options->hasElement() && $options->has('service') &&
            $options->has('env') &&
            in_array($options->get('env'), self::ENVS))
        {
            $host = HostNames::get($options->get('service'));
            $parts = explode('://', $host);
            self::$data->set('error', !$host);
            self::$data->set('env', $options->get('env', 'prod'));
            self::$data->set('username', $options->get('username', ''));
            self::$data->set('host', $host);
            self::$data->set('protocol', $parts[0]);
            self::$data->set('schema', $parts[0] . '://');
        }
    }

    public function isDEV(): bool {return self::$data->is('env', 'dev');}

    public function isQA(): bool {return self::$data->is('env', 'qa');}

    public function isSTAGE(): bool {return self::$data->is('env', 'stage');}

    public function isPROD(): bool {return self::$data->is('env', 'prod');}

    public function hasError(): bool {return self::$data->get('error');}

    public function name(): string {return self::$data->get('env');}

    public function getSchema(): string {return self::$data->get('schema');}

    public function getHost(): string {return self::$data->get('host');}

    public function getPath(string $uri): string {return (substr($uri, 0, 1) !== '/' ? '/' : '') . $uri;}

    public function getUrl(string $uri): string {return self::$data->get('host') . $this->getPath($uri);}
}