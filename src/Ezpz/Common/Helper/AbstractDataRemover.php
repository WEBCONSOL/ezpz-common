<?php

namespace Ezpz\Common\Helper;

use Ezpz\Common\ApiGateway\Endpoints;
use KafkaService\Constants;
use KafkaService\Producer;
use OAuth2Server\AccessTokenValidator;
use Utilities\HostNames;
use Utilities\HttpClient;
use WC\Security\Jwt;

abstract class AbstractDataRemover extends AbstractApiContextProcessor
{
    protected $tb = '';
    protected $condition = '';
    protected $kafkaKey = '';

    abstract public function onBeforeProcessRequest();
    abstract public function onAfterProcessRequest();

    protected function dataForKafka(): array {return [];}

    protected final function processRequest()
    {
        $this->onBeforeProcessRequest();

        if ($this->tb && $this->condition) {
            $sql = 'DELETE FROM '.$this->tb.' WHERE ' . $this->condition;
            $this->executeQuery($sql);
        }
        else {
            $this->setResponseStatus(false);
            $this->setResponseMessage('Internal Server Error. Database table and condition are missing.');
            $this->setResponseStatusCode(500);
        }

        $this->onAfterProcessRequest();

        Query::appendToResponseResult($this->result);

        // kafka producer invocation goes here
        if (KAFKA_INTEGRATED && $this->kafkaKey) {
            $this->sendKafkaTopic();
        }
    }

    protected function sendKafkaTopic() {
        // Apache Kafka
        $data = $this->dataForKafka();
        if(!empty($data)) {
            $producer = new Producer();
            $producer->setTopic(Constants::KAFKA_TOPIC_UPDATE_CLIENT);
            $producer->setKey($this->kafkaKey);
            if (!isset($data['action'])) {
                $data['action'] = Constants::KAFKA_ACTION_REMOVE;
                $accessToken = AccessTokenValidator::getAccessToken();
                $appName = AccessTokenValidator::getAppName();
                if ($accessToken && $appName) {
                    $uri = Endpoints::config('appIdForKafka', ['access_token'=>$accessToken, 'hashed_app_name'=>md5($appName)]);
                    $url = HostNames::getConfig() . $uri[1];
                    $subReqResponse = HttpClient::request($uri[0], $url, []);
                    $json = json_decode($subReqResponse->getBody(), true);
                    $data['app'] = Jwt::encryptData(isset($json['data']) && !empty($json['data']) ? $json['data'] : '');
                }
            }
            $producer->setValue([Constants::KAFKA_DATA_KEY_DATA=>$data]);
            $producer->send();
        }
    }
}