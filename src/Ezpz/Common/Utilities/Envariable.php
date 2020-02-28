<?php

namespace Ezpz\Common\Utilities;

use WC\Utilities\StringUtil;

final class Envariable
{
    private static $ENV = [];

    private function __construct(){}

    public static function get(string $k): string {
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
                        self::$ENV[$arr2[0]] = $arr2[1];
                    }
                }
            }
        }
    }
}