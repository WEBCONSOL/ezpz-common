<?php

namespace Ezpz\Common\WC\Security;

final class SSHGenToken
{
    public $token = '';
    public $jti = '';
    public $issuer = '';
    public $audience = '';
    public $phrase = '';
    public $client_id = '';
    public $app_name = '';

    public function __toString() {return json_encode($this);}
}