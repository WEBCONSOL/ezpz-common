<?php

namespace Ezpz\Common\Mailer;

use GX2CMS\TemplateEngine\GX2CMS;
use GX2CMS\TemplateEngine\Model\Context;
use GX2CMS\TemplateEngine\Model\Tmpl;
use WC\Models\ListModel;
use WC\Utilities\CustomResponse;

final class Mail
{
    const KEY_FROM_ADDRESS = "fromAddress";
    const KEY_FROM_NAME = "fromName";
    const KEY_TO_EMAIL = "email";
    const KEY_TO_NAME = "name";
    private $envelop;
    private $gx2cmsTmpl = true;

    public function __construct(array $to, string $tpl, array $data, string $subject, array $from=array(), bool $gx2cmsTmpl=true)
    {
        $this->gx2cmsTmpl = $gx2cmsTmpl;
        $this->prepareEnvelop($to, $tpl, $data, $subject, $from);
    }

    private function prepareEnvelop(array $to, string $tpl, array $data, string $subject, array $from=array())
    {
        if (empty($from)) {
            $from = new ListModel(PATH_COMMON_STATIC . DS . 'config' . DS . 'envelop.json');
        }
        else if (is_array($from)) {
            $from = new ListModel($from);
        }

        if (is_array($to)) {
            $to = new ListModel($to);
        }

        if (!$from->has(self::KEY_FROM_NAME) || !$from->has(self::KEY_FROM_ADDRESS) || !$to->has(self::KEY_TO_EMAIL) || !$to->has(self::KEY_TO_NAME) || empty($tpl))
        {
            CustomResponse::render(500, "Mail required data missing");
        }

        if (empty($subject))
        {
            CustomResponse::render(500, "Mail subject is missing");
        }

        if ($this->gx2cmsTmpl) {
            $ezpzTmpl = new GX2CMS();
            $emailMessage = $ezpzTmpl->compile(new Context($data), new Tmpl($tpl));
        }
        else if (!is_file($tpl) && !is_dir($tpl)) {
            $emailMessage = $tpl;
        }
        else {
            $emailMessage = '';
            CustomResponse::render(500, "Invalid tpl value. With this condition, it cannot be file or directory.");
        }

        $this->envelop = new Envelop(null);
        $this->envelop->from((string)$from->get(self::KEY_FROM_ADDRESS), (string)$from->get(self::KEY_FROM_NAME));
        $this->envelop->to((string)$to->get(self::KEY_TO_EMAIL), (string)$to->get(self::KEY_TO_NAME));
        $this->envelop->subject($subject);
        $this->envelop->message($emailMessage);
    }

    public function deliver() {return $this->envelop instanceof Envelop ? $this->envelop->deliver() : false;}
}