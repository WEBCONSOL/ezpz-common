<?php

namespace Ezpz\Common\Utilities;

final class HostNames
{
    private function __construct(){}

    public static function getAssets(string $env=''): string {return self::get('assets', $env);}
    public static function getAuth(string $env=''): string {return self::get('auth', $env);}
    public static function getCart(string $env=''): string {return self::get('cart', $env);}
    public static function getConfig(string $env=''): string {return self::get('config', $env);}
    public static function getGlobalProperties(string $env=''): string {return self::get('globalproperties', $env);}
    public static function getInstall(string $env=''): string {return self::get('install', $env);}
    public static function getOffer(string $env=''): string {return self::get('offer', $env);}
    public static function getPim(string $env=''): string {return self::get('pim', $env);}
    public static function getPos(string $env=''): string {return self::get('pos', $env);}
    public static function getPrice(string $env=''): string {return self::get('price', $env);}
    public static function getSession(string $env=''): string {return self::get('session', $env);}
    public static function getStoreFront(string $env=''): string {return self::get('storefront', $env);}
    public static function getStoreManager(string $env=''): string {return self::get('storemanager', $env);}
    public static function getAdmin(string $env=''): string {return self::get('admin', $env);}
    public static function getStore(string $env=''): string {return self::get('store', $env);}
    public static function getApi(string $env=''): string {return self::get('api', $env);}

    public static function get(string $service, string $env=''): string {
        if (!$env) {$env = Envariable::environment();}
        $https = filter_input(INPUT_SERVER, 'HTTPS');
        return 'http'.($https?'s://':'://').($env==='prod' || $env === '' ? '' : $env.'-').$service.'.ezpizee.com';
    }
}