<?php

namespace Ezpz\Common\WC\Security;

final class Token
{
    public $token = '';
    public $jti = '';
    public $env = '';
    public $appName = '';
    public $issuer = '';
    public $audience = '';
    public $ssh = '';
    public $client = ['client_id'=>'','client_secret'=>'','phrase'=>''];
    public $access_token = '';

    public function __toString() {return json_encode($this);}
}