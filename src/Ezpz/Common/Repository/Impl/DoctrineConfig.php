<?php

namespace Ezpz\Common\Repository\Impl;

use Ezpz\Common\Security\InternalJWT;
use WC\Utilities\CustomResponse;
use Doctrine\DBAL\DBALException;
use Ezpz\Common\Repository\DbConfigInterface;
use Ezpz\Common\Utilities\HostNames;
use Ezpz\Common\Utilities\HttpClient;
use WC\Models\ListModel;
use WC\Utilities\EncodingUtil;
use WC\Utilities\Logger;

class DoctrineConfig implements DbConfigInterface
{
    private $settings = array();

    public function loadSettings(ListModel $settings, ListModel $configParams)
    {
        $config = $this->getConfig($configParams);

        if (isset($config['driver']) && isset($config['host']) && isset($config['port']) &&
            isset($config['dbname']) && (isset($config['user'])||isset($config['username'])) && isset($config['password']) && isset($config['charset']))
        {
            if (!isset($config['user']) && isset($config['username'])) {
                $config['user'] = $config['username'];
            }

            $this->settings = array(
                'dev_mode' => true,
                'cache_dir' => $settings->get('cache_dir', '/cache'),
                'metadata_dirs' => $settings->has('metadata_dirs') ? $settings->get('metadata_dirs') : array(),
                'connection' => $config
            );
        }
        else
        {
            Logger::error("invalid_config_data (".DoctrineConfig::class.")");
            CustomResponse::render(500, 'invalid_config_data');
        }
    }

    public function getAsArray(): array {return $this->settings;}

    private function getConfig(ListModel $configParams): array
    {
        $config = array();
        if ($configParams->get('force_config', false)) {
            $file = EZPZ_ROOT . DS . 'config' . DS . $configParams->get('entity') . '.json';
            if (file_exists($file)) {
                $config = json_decode(file_get_contents($file), true);
                if (isset($config['content'])) {
                    $content = InternalJWT::decrypt($config['content']);
                    if (!empty($content)) {
                        $config = json_decode($content, true);
                    }
                }
            }
        }
        else if (EZPZ_LOCAL_MICROSERVICE) {
            $config = $this->loadLocally($configParams);
        }
        else {
            $config = $this->loadViaHttp($configParams);
        }

        return $config;
    }

    private function loadLocally(ListModel $configParams): array {
        $config = array();
        $file = EZPZ_ROOT . DS . 'config/oauth.json';
        if (file_exists($file)) {
            try {
                $connectionParams = json_decode(file_get_contents($file), true);
                if (!empty($connectionParams) && isset($connectionParams['content'])) {
                    $connectionParams['content'] = InternalJWT::decrypt($connectionParams['content']);
                    if (!empty($connectionParams['content'])) {
                        $connectionParams = json_decode($connectionParams['content'], true);
                    }
                }
                if ($configParams->is('type', 'static')) {
                    return $connectionParams;
                }
                $dbalConfig = new \Doctrine\DBAL\Configuration();
                $conn = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $dbalConfig);
                $query = 'SELECT b.content AS data '.
                    'FROM oauth_users a LEFT JOIN userconfig b on b.user_id=a.id '.
                    'WHERE a.username="'.$configParams->get('user').'"';
                $result = $conn->executeQuery($query)->fetch(\PDO::FETCH_ASSOC);
                if ($result) {
                    if (EncodingUtil::isValidJSON($result['data'])) {
                        $data = json_decode($result['data'], true);
                    }
                    else if (is_array($result['data'])) {
                        $data = $result['data'];
                    }
                    else {
                        $data = InternalJWT::decrypt($result['data']);
                        if (!empty($data) && EncodingUtil::isValidJSON($data)) {
                            $data = json_decode($data, true);
                        }
                        else {
                            $data = [];
                        }
                    }
                    if (is_array($data) && !empty($data) && isset($data[$configParams->get('entity')])) {
                        $data = $data[$configParams->get('entity')];
                        if (isset($data[$configParams->get('env')])) {
                            if (isset($data[$configParams->get('env')][$configParams->get('service')])) {
                                $config = $data[$configParams->get('env')][$configParams->get('service')];
                                $this->formatConfig($config);
                            }
                            else if (isset($data[$configParams->get('env')]['cart'])) {
                                $config = $data[$configParams->get('env')]['cart'];
                                $this->formatConfig($config);
                            }
                        }
                    }
                }
            }
            catch (DBALException $e) {
                Logger::error($e->getMessage()." (".DoctrineConfig::class.")");
                CustomResponse::render($e->getCode(), "DoctrineConfig DBALException. " . $e->getMessage() . ' ('.$e->getCode().')');
            }
        }
        return $config;
    }

    private function loadViaHttp(ListModel $configParams): array {
        $config = array();
        if ($configParams->is('type', 'static')) {
            $endpoint = HostNames::getConfig($configParams->get('env')) . '/static/' . $configParams->get('env') . '/' . $configParams->get('entity');
            $response = HttpClient::request('GET', $endpoint);
            if ($response->getBody()) {
                $config = json_decode($response->getBody()->getContents(), true);
                if (isset($config['data'])) {
                    $config = $config['data'];
                }
            }
        }
        else if ($configParams->is('type', 'user')) {
            $uris = [$configParams->get('user'), $configParams->get('entity'), $configParams->get('env'), $configParams->get('service')];
            $endpoint = HostNames::getConfig($configParams->get('env')) . '/user/' . implode('/', $uris);
            $response = HttpClient::request('GET', $endpoint);
            if ($response->getBody()) {
                $buffer = $response->getBody()->getContents();
                if (EncodingUtil::isValidJSON($buffer)) {
                    $config = json_decode($buffer, true);
                    if (isset($config['data'])) {
                        $config = $config['data'];
                        $this->formatConfig($config);
                    }
                }
            }
        }
        return $config;
    }

    private function formatConfig(array &$config) {
        if (!isset($config['charset'])) {$config['charset'] = 'utf8';}
        if (!isset($config['port'])) {$config['port'] = 3306;}
        if (!isset($config['dbname']) && isset($config['database'])) {$config['dbname'] = $config['database'];unset($config['database']);}
        if (isset($config['driver']) && $config['driver'] === 'mysql') {$config['driver'] = 'pdo_mysql';}
    }
}