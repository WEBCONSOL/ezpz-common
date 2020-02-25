<?php

namespace Ezpz\Common\Mailer;

final class SMTP extends MailServiceAbstract
{
    public function __construct($val)
    {
        parent::__construct($val);

        if (!($this->has('username') && $this->has('password') && $this->has('host') && $this->has('port') && $this->has('secure'))) {
            $this->reset(null);
        }
    }

    public function getUsername() {return $this->get('username');}
    public function getPassword() {return $this->get('password');}
    public function getHost() {return $this->get('host');}
    public function getPort() {return $this->get('port');}
    public function getSecure() {return $this->get('secure');}
}