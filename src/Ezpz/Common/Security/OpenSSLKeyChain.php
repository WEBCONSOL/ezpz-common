<?php

namespace Ezpz\Common\WC\Security;

final class OpenSSLKeyChain
{
    public $csr = '';
    public $cert = '';
    public $key = '';
    public $phrase = '';

    public function __toString() {return json_encode($this);}
}