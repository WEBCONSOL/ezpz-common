<?php

namespace Ezpz\Common\ApiGateway;

use Ezpz\Common\Repository\Impl\DoctrineConfig;
use Slim\Http\Request;
use Slim\Http\Response;
use Ezpz\Common\SharedServiceProvider\Doctrine;
use Ezpz\Common\Utilities\HttpClient;
use WC\Models\ListModel;
use WC\Utilities\CustomResponse;
use WC\Utilities\EncodingUtil;

class ServiceContext {

    private $service = null;
    private $path = null;

    public function __construct($service, $path=null) {
        $this->service = $service;
        $this->path = $path;
    }

    public function __invoke(Request $request, Response $response, array $args): Response {
        if (EZPZ_LOCAL_MICROSERVICE) {
            return $this->loadLocally($request, $response, $args);
        }
        else {
            return $this->loadViaHttp($request, $response, $args);
        }
    }

    public static function loadServiceContextProcessor(ServiceContextConfig $config): Response {
        \WC\Utilities\CustomErrorHandler::init(false);
        \Ezpz\Common\CustomAutoload::exec();
        $slimConfig = new SlimSettings();
        $doctrineConfig = new DoctrineConfig();
        $doctrineConfig->loadSettings($config->getRepositoryConfigParams(), $config->getRepositoryConfigParams());
        $slimConfig->append('doctrine', $doctrineConfig->getAsArray());
        $container = new \Slim\Container($slimConfig->getAsArray());
        $container[NOT_FOUND_HANDLER] = new \Ezpz\Common\ApiGateway\SlimNotFoundHandler();
        $container[ERROR_HANDLER] = new \Ezpz\Common\ApiGateway\CustomErrorHandler();
        $container[NOT_ALLOWED_HANDLER] = new \Ezpz\Common\ApiGateway\SlimNotAllowedHandler();
        $serviceProviderName = $config->getServiceNamespace();
        $container->register(new Doctrine())->register(new $serviceProviderName($config->getSlimServiceProviderSetting()));
        $container[\Slim\App::class];
        return $container['response'];
    }

    private function loadLocally(Request $request, Response $response, array $args): Response {
        $serviceContextConfig = new ServiceContextConfig($this->service);
        if ($serviceContextConfig->hasConfig()) {
            $serviceContextConfig = new ServiceContextConfig($this->service);
            return self::loadServiceContextProcessor($serviceContextConfig)->withHeader('Content-Type', 'application/json; charset=utf-8');
        }
        else {
            $uri = $request->getUri()->getPath();
            return $response->withJson(array(
                'success' => false,
                'data' => array(array('service' => $this->service, 'uri' => $uri)),
                'message' => 'The Micro Service does not exist'
            ));
        }
    }

    private function loadViaHttp(Request $request, Response $response, array $args): Response {
        if (empty($this->path)) {
            $this->path = isset($args['path']) && strlen($args['path']) > 0 && $args['path'] != '/' ? $args['path'] : null;
        }

        if (!empty($this->path))
        {
            $env = $this->getEnv();

            if ($env !== null)
            {
                if (!$env->hasError())
                {
                    $options['verify'] = PATH_COMMON_STATIC . "/ssl-cert/".EZPZ_ENV."-cacert.pem";
                    $method = strtoupper($request->getMethod());
                    $body = $request->getParsedBody();
                    if (!empty($body)) {
                        $files = isset($_FILES) && is_array($_FILES) && sizeof($_FILES) ? $_FILES : array();
                        if (!empty($files)) {
                            $options['multipart'] = array();
                            foreach ($body as $name=>$contents) {
                                $param = array('name'=>$name, 'contents'=>$contents);
                                $options['multipart'][] = $param;
                            }
                            foreach ($files as $name=>$contents) {
                                $file = array('name'=>$name, 'contents'=>fopen($contents['tmp_name'], 'r'), 'filename'=>$contents['name']);
                                $options['multipart'][] = $file;
                            }
                        }
                        else {
                            $options['form_params'] = $body;
                        }
                    }
                    $headers = $this->requestHeader($request);
                    if (!empty($headers)) {
                        $options['headers'] = $headers;
                    }
                    //die(json_encode($headers));
                    $params = $request->getQueryParams();
                    if (!empty($params)) {
                        $this->path = $this->path . $this->getQueryParamsAsString($request);
                    }
                    $clientResponse = HttpClient::request($method, $env->getUrl($this->path), $options);
                    $output = $clientResponse->getBody()->getContents();
                    if (EncodingUtil::isValidJSON($output)) {
                        $output = json_decode($output, true);
                    }
                    else {
                        $output = CustomResponse::getOutputFormattedAsArray(array('serviceContextCatchError' => $output));
                    }
                }
                else
                {
                    $message = 'Cannot find the requested service: ' . $this->service . '.';
                    $output = CustomResponse::getOutputFormattedAsArray(null, 500, $message);
                }
            }
            else
            {
                $output = CustomResponse::getOutputFormattedAsArray(null, 403);
            }
        }
        else
        {
            $output = CustomResponse::getOutputFormattedAsArray(null, 404);
        }

        return $response->withJson($output);
    }

    private function getEnv(): Env
    {
        $matches = array();
        $matches['service'] = $this->service;
        $matches['env'] = EZPZ_ENV;
        $matches['username'] = EZPZ_USERNAME;
        $matches = new ListModel($matches);
        return new Env($matches);
    }

    private function requestHeader(Request &$request): array
    {
        $headers = array();
        $requestHeaders = getallheaders();
        if (!empty($requestHeaders)) {
            foreach($requestHeaders as $key=>$val) {
                if (in_array($key, Constants::ALLOWED_HEADER_PARAMS) && !empty($val)) {
                    $headers[$key] = $val;
                }
            }
        }
        else {
            foreach(Constants::ALLOWED_HEADER_PARAMS as $key) {
                if ($request->hasHeader($key)) {
                    $val = $request->getHeaderLine($key);
                    if (!empty($val)) {
                        $headers[$key] = $val;
                    }
                }
            }
        }
        return $headers;
    }

    private function getQueryParamsAsString(Request &$request): string
    {
        $params = $request->getQueryParams();

        if (!empty($params))
        {
            return '?' . http_build_query($params);
        }

        return '';
    }
}