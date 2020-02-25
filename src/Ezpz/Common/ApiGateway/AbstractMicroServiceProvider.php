<?php

namespace Ezpz\Common\ApiGateway;

use Doctrine\ORM\EntityManager;
use Pimple\ServiceProviderInterface;
use Slim\App;
use Pimple\Container;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;
use WC\Utilities\PathUtil;

abstract class AbstractMicroServiceProvider implements ServiceProviderInterface
{
    protected static $registerLoaded = false;
    protected static $endpoints = array();
    protected static $container;
    protected $serviceKey = '';
    protected $serviceNameSpacePfx = '';

    abstract protected function init(Container &$container);

    public function __construct(array $settings=null)
    {
        if ($settings !== null && sizeof($settings))
        {
            $this->serviceKey = isset($settings['serviceKey']) ? $settings['serviceKey'] : '';
            $this->serviceNameSpacePfx = isset($settings['serviceNameSpacePfx']) ? $settings['serviceNameSpacePfx'] : '';
        }
    }

    public final function register(Container $container)
    {
        //$userAgent = $container['request']->getHeaderLine('User-Agent');
        //if ($userAgent !== EZPZ_USER_AGENT) {CustomResponse::render(403, 'Your request is forbidden ('.$userAgent.')');}

        $this->init($container);
        self::$container = $container;

        $container[App::class] = function (Container $cnt): App
        {
            $app = new App($cnt);
            $uri = $this->serviceUri($cnt);
            $uris = Endpoints::{$this->serviceKey}('');
            $serviceAction = null;
            $found = false;

            foreach ($uris as $action=>$item)
            {
                if (is_array($item) && isset($item['uri']) && isset($item['method']) &&
                    in_array(strtoupper($item['method']), Constants::METHODS) &&
                    PathUtil::isUriMatch($item['uri'], $uri))
                {
                    $args = PathUtil::getUriArgs($item['uri'], $uri);
                    $className = $this->serviceNameSpacePfx . '\\Action\\' . ucwords($action);

                    if (isset($cnt[$className]))
                    {
                        $serviceAction = $cnt[$className];
                    }
                    else if (isset(self::$container[$className]))
                    {
                        $serviceAction = self::$container[$className];
                    }
                    else
                    {
                        $serviceAction = new $className($cnt[EntityManager::class]);
                        $cnt[$className] = $serviceAction;
                        self::addContainer($className, $serviceAction);
                    }

                    $app->respond($serviceAction($cnt['request'], $cnt['response'], $args));

                    $found = true;

                    break;
                }
            }

            if (!$found) {
                Logger::error('404. '.$uri.' ('.get_called_class().')');
                CustomResponse::render(404);
            }

            return $app;
        };
    }

    public final static function getContainer(): Container {return self::$container;}

    public final static function addContainer(string $key, $obj) { if(!isset(self::$container) && is_object($obj)){self::$container[$key]=$obj;}}

    protected final function serviceUri(Container &$cnt) {
        $req = $cnt['request'];
        $parts = explode('/', trim($req->getUri()->getPath(), '/'));
        if (in_array($parts[0], Constants::API_ACTIVE_VERSIONS) && isset($parts[1]) && $parts[1] === $this->serviceKey) {
            unset($parts[0],$parts[1]);
        }
        return '/' . implode('/', $parts);
    }
}