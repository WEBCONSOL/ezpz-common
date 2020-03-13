<?php

namespace Ezpz\Common\KafkaService;

use \Ezpz\Common\ApiGateway\Env;

final class Constants
{
    const KAFKA_METADATA_REFRESH_INTERVAL_MS = 10000;
    const KAFKA_METADATA_BROKER_IP_LOCAL = '127.0.0.1';
    const KAFKA_METADATA_BROKER_IP_DEV = '198.211.104.233'; // dev-admin.ezpizee.com
    const KAFKA_METADATA_BROKER_IP_QA = '198.211.104.233'; // dev-admin.ezpizee.com
    const KAFKA_METADATA_BROKER_IP_STAGE = '198.211.104.233'; // dev-admin.ezpizee.com
    const KAFKA_METADATA_BROKER_IP_PROD = '198.211.104.233'; // dev-admin.ezpizee.com
    const KAFKA_METADATA_BROKER_PORT = '9092';
    const KAFKA_BROKER_VERSION = '1.0.0';
    const KAFKA_REQUIRED_ACK = 1;
    const KAFKA_PRODUCE_INTERVAL = 500;
    // const KAFKA_GROUP_ID = 'Ezpizee';

    const KAFKA_TOPIC_UPDATE_CLIENT = EZPZ_ENV.'-updateclient'; //EZPZ_ENV: local, dev, qa, stage, or prod

    const KAFKA_KEY_GLOBAL_PROPERTIES_COUNTRIES =      'Country';
    const KAFKA_KEY_GLOBAL_PROPERTIES_CURRENCIES =     'Currency';
    const KAFKA_KEY_GLOBAL_PROPERTIES_TEMPERATURES =   'Temperature';
    const KAFKA_KEY_GLOBAL_PROPERTIES_LENGTH =         'LengthMeasurement';
    const KAFKA_KEY_GLOBAL_PROPERTIES_WEIGHT =         'WeightMeasurement';
    const KAFKA_KEY_GLOBAL_PROPERTIES_ADDRESSES =      'Address';
    const KAFKA_KEY_GLOBAL_PROPERTIES_ASSETS =         'Asset';
    const KAFKA_KEY_STORE_MANAGER_STORES =             'Store';
    const KAFKA_KEY_STORE_MANAGER_CATEGORIES =         'Category';
    const KAFKA_KEY_STORE_MANAGER_MANUFACTURERS =      'Manufacturer';
    const KAFKA_KEY_STORE_MANAGER_TAXES =              'Tax';
    const KAFKA_KEY_STORE_MANAGER_PAYMENT_METHODS =    'PaymentMethod';
    const KAFKA_KEY_STORE_MANAGER_SHIPPING_METHODS =   'ShippingMethod';
    const KAFKA_KEY_PRODUCT_MANAGER_ATTRIBUTES =       'Attribute';
    const KAFKA_KEY_PRODUCT_MANAGER_TYPES =            'Type';
    const KAFKA_KEY_PRODUCT_MANAGER_PRODUCTS =         'Product';
    const KAFKA_KEY_OFFER_MANAGER_DISCOUNTS =          'Discount';
    const KAFKA_KEY_OFFER_MANAGER_BUNDLES =            'Bundle';
    const KAFKA_KEY_OFFER_MANAGER_CROSS_SELLS =        'Cross-Sell';
    const KAFKA_KEY_OFFER_MANAGER_UP_SELLS =           'Up-Sell';

    const KAFKA_DATA_KEY_TOPIC = 'topic';
    const KAFKA_DATA_KEY_KEY = 'key';
    const KAFKA_DATA_KEY_VALUE = 'value';
    const KAFKA_DATA_KEY_DATA = 'data';

    const KAFKA_ACTION_ADD = "add";
    const KAFKA_ACTION_UPDATE = "update";
    const KAFKA_ACTION_REMOVE = "remove";

    public static function getBrokerIP() {
        $ip = "";
        switch (EZPZ_ENV) {
            case Env::ENVS[0]:
                $ip = self::KAFKA_METADATA_BROKER_IP_LOCAL;
                break;

            case Env::ENVS[1]:
                $ip = self::KAFKA_METADATA_BROKER_IP_DEV;
                break;

            case Env::ENVS[2]:
                $ip = self::KAFKA_METADATA_BROKER_IP_QA;
                break;

            case Env::ENVS[3]:
                $ip = self::KAFKA_METADATA_BROKER_IP_STAGE;
                break;

            case Env::ENVS[4]:
                $ip = self::KAFKA_METADATA_BROKER_IP_PROD;
                break;
        }
        return $ip;
    }
}
