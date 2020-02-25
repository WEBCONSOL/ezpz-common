<?php

namespace Ezpz\Common\KafkaService;

use Kafka\Exception;
use WC\Utilities\Logger;

class Producer
{
    private $config;
    private $producer;
    private $error = false;
    private $data = [];

    public function __construct()
    {
        if (KAFKA_INTEGRATED) {
            try {
                $this->config = \Kafka\ProducerConfig::getInstance();
                $this->config->setMetadataRefreshIntervalMs(Constants::KAFKA_METADATA_REFRESH_INTERVAL_MS);
                $this->config->setMetadataBrokerList(Constants::getBrokerIP() . ':' . Constants::KAFKA_METADATA_BROKER_PORT);
                $this->config->setBrokerVersion(Constants::KAFKA_BROKER_VERSION);
                $this->config->setRequiredAck(Constants::KAFKA_REQUIRED_ACK);
                $this->config->setIsAsyn(false);
                $this->config->setProduceInterval(Constants::KAFKA_PRODUCE_INTERVAL);
                $this->producer = new \Kafka\Producer();
            }
            catch (Exception $e) {
                $this->error = true;
                $this->data = [];
                Logger::error("Kafka Error during instantiation: " . $e->getMessage());
            }
        }
    }

    public function setTopic(string $str) {$this->setData(Constants::KAFKA_DATA_KEY_TOPIC, $str);}
    public function setKey(string $str) {$this->setData(Constants::KAFKA_DATA_KEY_KEY, $str);}
    public function setValue(array $arr) {$this->setData(Constants::KAFKA_DATA_KEY_VALUE, json_encode($arr));}
    public function setData(string $k, string $v) {$this->data[$k]=$v;}

    public function send() {
        if (KAFKA_INTEGRATED && !$this->error && !empty($this->data) &&
            isset($this->data[Constants::KAFKA_DATA_KEY_TOPIC]) &&
            isset($this->data[Constants::KAFKA_DATA_KEY_VALUE])) {
            if (!isset($this->data[Constants::KAFKA_DATA_KEY_KEY])) {
                $this->data[Constants::KAFKA_DATA_KEY_KEY] = 'ezpizee';
            }
            try {
                $this->producer->send([$this->data]);
            }
            catch (Exception $e) {
                Logger::error("Kafka Error during sending the message: " . $e->getMessage());
            }
        }
    }
}
