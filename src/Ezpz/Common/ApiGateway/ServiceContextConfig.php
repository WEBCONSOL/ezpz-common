<?php

namespace Ezpz\Common\ApiGateway;

use \Ezpz\Common\Utilities\Envariable;
use WC\Models\ListModel;

class ServiceContextConfig {

    private $data = ['repoConfigParams'=>[], 'root'=>null, 'serviceNamespacePfx'=>'', 'hasConfig'=>false, 'serviceName'=>''];

    public function __construct(string $serviceRoot)
    {
        $this->data['root'] = $serviceRoot;
        $this->data['serviceName'] = Envariable::serviceName();
        $this->data['serviceNamespacePfx'] = Envariable::serviceNameSpacePfx();
        $this->data['repoConfigParams']['env'] = Envariable::environment();
        $this->data['repoConfigParams']['user'] = Envariable::username();
        $this->data['repoConfigParams']['service'] = Envariable::serviceName();

        // static or user. Default: user
        $this->data['repoConfigParams']['type'] = Envariable::serviceType();
        if (!$this->data['repoConfigParams']['type']) {$this->data['repoConfigParams']['type'] = 'user';}
        // commerce, oauth, etc. Default: commerce
        $this->data['repoConfigParams']['entity'] = Envariable::serviceEntity();
        if (!$this->data['repoConfigParams']['entity']) {$this->data['repoConfigParams']['entity'] = 'commerce';}
        // it's mainly for the config service
        $this->data['repoConfigParams']['force_config'] = Envariable::serviceForceConfig();
    }

    public function hasConfig():bool {return $this->data['hasConfig'];}
    public function getRoot():string {return $this->data['root'];}
    public function getServiceNamespacePfx():string {return $this->data['serviceNamespacePfx'];}
    public function getServiceName():string {return $this->data['serviceName'];}
    public function getServiceNamespace():string {return $this->getServiceNamespacePfx() . '\\Provider\\Slim';}
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