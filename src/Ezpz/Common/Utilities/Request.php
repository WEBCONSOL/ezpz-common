<?php

namespace Ezpz\Common\Utilities;

class Request extends \WC\Utilities\Request
{
    private $allowedMethods = array('GET','PUT','DELETE','POST','PATCH');

    public function allowedMethodsAsString(): string {return implode(',', $this->allowedMethods);}

    public function isTheSameHostname(): bool {
        if ($this->originHost()) {
            $parts = explode('.', $this->host());
            $size = sizeof($parts);
            if ($size > 1) {
                $host1 = $parts[$size-2].'.'.$parts[$size-1];
                $parts = explode('.', $this->originHost());
                $size = sizeof($parts);
                if ($size > 1) {
                    $host2 = $parts[$size-2].'.'.$parts[$size-1];
                    return $host1 === $host2;
                }
            }
        }
        return false;
    }

    public function getParam(string $key, $default=null) {$v = parent::getParam($key); return $v?$v:$default;}

    /**
     * @param $key
     *
     * @return string
     */
    public function getHeaderLine($key): string {return $this->getHeaderParam($key);}
}