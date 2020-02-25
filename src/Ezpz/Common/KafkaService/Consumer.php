<?php

namespace Ezpz\Common\KafkaService;

include __DIR__ . '/../../defines.php';
include PATH_VENDOR . DS . 'autoload.php';

class Consumer
{
    private function __construct(){}

    public static function consume() {

        if (KAFKA_INTEGRATED) {
            $config = \Kafka\ConsumerConfig::getInstance();
            $config->setMetadataRefreshIntervalMs(Constants::KAFKA_METADATA_REFRESH_INTERVAL_MS);
            $config->setMetadataBrokerList(Constants::getBrokerIP().':'.Constants::KAFKA_METADATA_BROKER_PORT);
            $config->setGroupId(Constants::KAFKA_GROUP_ID);
            $config->setBrokerVersion(Constants::KAFKA_BROKER_VERSION);
            $config->setTopics([Constants::KAFKA_TOPIC_UPDATE_CLIENT]);
            $consumer = new \Kafka\Consumer();
            $consumer->start(function($topic, $part, $message) {
                // var_dump($topic);
                // var_dump($part);
                var_dump($message);
            });
        }
    }
}