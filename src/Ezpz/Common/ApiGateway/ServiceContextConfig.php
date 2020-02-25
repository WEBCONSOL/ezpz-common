<?php

namespace Ezpz\Common\ApiGateway;

use WC\Models\ListModel;

class ServiceContextConfig {

    private $data = ['repoConfigParams'=>[], 'root'=>null, 'serviceNamespacePfx'=>'', 'hasConfig'=>false, 'serviceName'=>''];

    public function __construct(string $service)
    {
        die('100');
        $this->data['root'] = $_SERVER['DOCUMENT_ROOT'] . DS . 'service_' . strtolower($service);
        $serviceConfig = $this->data['root'] . DS . 'config.json';
        $this->data['hasConfig'] = file_exists($serviceConfig);
        if ($this->data['hasConfig']) {
            $this->data['serviceName'] = strtolower($service);
            $obj = json_decode(file_get_contents($serviceConfig), true);
            $this->data['serviceNamespacePfx'] = isset($obj['serviceNamespacePfx']) ? $obj['serviceNamespacePfx'] : null;

            $this->data['repoConfigParams']['user'] = EZPZ_USERNAME;
            $this->data['repoConfigParams']['service'] = strtolower($service);

            // static or user. Default: user
            $this->data['repoConfigParams']['type'] = isset($obj['type']) ? $obj['type'] : 'user';
            // commerce, oauth, etc. Default: commerce
            $this->data['repoConfigParams']['entity'] = isset($obj['entity']) ? $obj['entity'] : 'commerce';
            // it's mainly for the config service
            $this->data['repoConfigParams']['force_config'] = isset($obj['force_config']) ? $obj['force_config'] : false;
        }
    }

    public function hasConfig():bool {return $this->data['hasConfig'];}
    public function getRoot():string {return $this->data['root'];}
    public function getServiceNamespacePfx():string {return $this->data['serviceNamespacePfx'];}
    public function getServiceName():string {return $this->data['serviceName'];}
    public function getServiceNamespace():string {return $this->getServiceNamespacePfx() . '\\Provider\\Slim';}
    public function getServiceAutoloadClass(): string {return $this->getServiceNamespacePfx() . 'Autoload';}
    public function getServiceAutoloadPath(): string {return $this->data['root'] . DS . $this->getServiceAutoloadClass() . '.php';}
    public function getRepositoryConfigParams(): ListModel {return new ListModel($this->data['repoConfigParams']);}
    public function getSlimServiceProviderSetting(): array {
        return ['serviceNameSpacePfx' => $this->data['serviceNamespacePfx'], 'serviceKey' => $this->data['serviceName']];
    }
    public function getDoctrineSettings(): ListModel {
        return new ListModel([
            'cache_dir' => $this->getRoot() . DS . 'cache' . DS . 'doctrine',
            'metadata_dirs' => glob($this->getRoot() . DS . 'src' . DS . $this->getServiceName() . DS . 'Domain')
        ]);
    }
    public function getData(): array {return $this->data;}
}