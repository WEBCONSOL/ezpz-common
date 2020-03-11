<?php

namespace Ezpz\Common;

class CustomAutoload
{
    protected static $packages = array();
    protected static $objects = array();
    protected static $delimiter = "\\";

    public final static function exec() {
        spl_autoload_register(function($class){
            $parts = explode(self::$delimiter, trim($class, self::$delimiter));
            $passed = false;
            if (in_array($class, self::$objects)) {
                $passed = true;
            }
            else if (in_array($parts[0], self::$packages)) {
                $file = self::root() . DS . str_replace('\\', DS, $class) . '.php';
                if (file_exists($file)) {
                    self::$objects[] = $class;
                    include $file;
                    $passed = true;
                }
            }
            return $passed;
        });
    }

    public final static function root(): string {return $_SERVER['DOCUMENT_ROOT'].DS.'src';}

    public final static function setPackages(array $packages) {self::$packages = $packages;}
}