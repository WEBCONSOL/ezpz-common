<?php

namespace Ezpz\Common\Mailer;

use WC\Utilities\CustomResponse;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class Envelop
{
    private $mailer = null;

    public function __construct($mailConfig)
    {
        $this->mailer = new PHPMailer();
        $this->mailer->isHTML(true);

        if ($mailConfig instanceof SMTP && $mailConfig->hasElement())
        {
            $this->mailer->isSMTP();
            $this->mailer->SMTPAuth = true;
            $this->mailer->Host = $mailConfig->getHost();
            $this->mailer->Port = $mailConfig->getPort();
            $this->mailer->Username = $mailConfig->getUsername();
            $this->mailer->Password = $mailConfig->getPassword();
            $this->mailer->SMTPSecure = $mailConfig->getSecure();
            $this->mailer->Port = $mailConfig->getPort();
        }
    }

    public function from(string $addr, string $name, $asReplyTo=false) {
        try {
            $this->mailer->setFrom($addr, $name);
            if ($asReplyTo) {
                $this->replyTo($addr, $name);
            }
        }
        catch (Exception $e) {
            CustomResponse::render($e->getCode(), $e->getMessage());
        }
    }

    public function to(string $addr, string $name) {$this->mailer->addAddress($addr, $name);}

    public function subject(string $subject) {$this->mailer->Subject = $subject;}

    public function message(string $message) {
        $this->mailer->Body = $message;
        $this->mailer->AltBody = strip_tags($message);
    }

    public function replyTo(string $addr, string $name) {$this->mailer->addReplyTo($addr, $name);}

    public function addCC(string $addr, string $name) {$this->mailer->addCC($addr, $name);}

    public function addBCC(string $addr, string $name) {$this->mailer->addBCC($addr, $name);}

    public function addAttachment(string $addr, string $name) {
        try {
            $this->mailer->addAttachment($addr, $name);
        }
        catch (Exception $e) {
            CustomResponse::render($e->getCode(), $e->getMessage());
        }
    }

    public function deliver() {
        try {
            return $this->mailer->send();
        }
        catch (Exception $e) {
            CustomResponse::render($e->getCode(), $e->getMessage());
        }
        return false;
    }
}