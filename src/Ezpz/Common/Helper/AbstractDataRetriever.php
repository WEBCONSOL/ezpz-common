<?php

namespace Ezpz\Common\Helper;

use WC\Utilities\Logger;

abstract class AbstractDataRetriever extends AbstractApiContextProcessor
{
    protected $tb = '';
    protected $condition = '';
    protected $fetchSingleRow = false;
    protected $fetchAll = false;
    protected $sql = '';
    private $hasResult = false;

    abstract public function onBeforeProcessRequest();

    abstract public function onAfterProcessRequest();

    protected final function processRequest()
    {
        $this->onBeforeProcessRequest();
        $sql = $this->sql ? $this->sql : ($this->tb ? 'SELECT * FROM '.$this->tb. ($this->condition ? ' WHERE '.$this->condition : '') : '');

        if ($sql) {
            if ($this->fetchSingleRow) {
                $this->setResponseData($this->fetchRow($sql));
            }
            else {
                $sql = $sql. ($this->fetchAll ? '' : ' ' . Query::queryLimitAndPaginationParams($this->request));
                $this->setResponseData($this->fetchRows($sql));
            }
            $this->hasResult = is_array($this->result) && sizeof($this->result) > 0;
        }
        else {
            $this->setResponseStatus(false);
            $this->setResponseStatusCode(500);
            $this->setResponseMessage('Internal Server Error. Database table is missing.');
        }

        if (!is_array($this->result)) {
            $this->result = [];
        }

        $this->onAfterProcessRequest();

        $this->pagination();
    }

    protected function hasResult(): bool {return $this->hasResult;}
}