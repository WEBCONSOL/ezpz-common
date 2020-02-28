<?php

namespace Ezpz\Common\Utilities;

use WC\Utilities\StringUtil;

final class Envariable
{
    private static $ENV = [];

    private function __construct(){}

    public static function environment() {return self::get('EZPZ_ENV');}
    public static function serviceName() {return self::get('EZPZ_SERVICE');}
    public static function serviceType() {return self::get('EZPZ_SERVICE_TYPE');}
    public static function serviceEntity() {return self::get('EZPZ_SERVICE_ENTITY');}
    public static function serviceForceConfig() {return self::get('EZPZ_SERVICE_FORCECONFIG');}
    public static function serviceNameSpacePfx() {return self::get('EZPZ_SERVICE_NAMESPACEPFX');}

    public static function username() {return self::get(HEADER_USER_NAME);}


    public static function get(string $k) {
        self::load(EZPZ_ROOT.DS.'.env');
        return isset(self::$ENV[$k]) ? self::$ENV[$k] : '';
    }

    public static function getAsArray(): array {
        self::load(EZPZ_ROOT.DS.'.env');
        return self::$ENV;
    }

    public static function load(string $path) {
        if (empty(self::$ENV)) {
            if (file_exists($path)) {
                $arr = explode("\n", file_get_contents($path));
                foreach ($arr as $line) {
                    $line = trim($line);
                    if ($line && !StringUtil::startsWith($line, "#")) {
                        $arr2 = explode("=", $line);
                        $arr2[0] = trim($arr2[0]);
                        if (isset($arr2[1])) {
                            $arr3 = [];
                            for($i=1; $i<sizeof($arr2); $i++) {
                                $arr3[] = $arr2[$i];
                            }
                            $arr2[1] = implode('=', $arr3);
                        }
                        self::$ENV[$arr2[0]] = $arr2[1]==='true'?true:($arr2[1]==='false'?false:$arr2[1]);
                    }
                }
                $request = new Request();
                self::$ENV[HEADER_USER_NAME] = $request->getHeaderParam(HEADER_USER_NAME, '');
            }
        }
    }
}