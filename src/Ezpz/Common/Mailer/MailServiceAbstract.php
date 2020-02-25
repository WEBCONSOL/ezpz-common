<?php

namespace Ezpz\Common\Mailer;

abstract class MailServiceAbstract implements \JsonSerializable, \Countable
{
    private $valueType = null;
    private $data = array();

    function __construct($val)
    {
        $this->reset($val);
    }

    public function reset($v=null)
    {
        if (is_array($v))
        {
            $this->data = $v;
            $this->valueType = 'array';
        }
        else if (is_object($v))
        {
            $this->data = json_decode(json_encode($v), true);
            $this->valueType = 'array';
        }
        else if (file_exists($v) && pathinfo($v, PATHINFO_EXTENSION) === 'json')
        {
            $this->data = json_decode(file_get_contents($v), true);
            $this->valueType = 'array';
        }
    }

    protected final function get($k=null, $default='') {
        if ($k !== null && isset($this->data[$k])) {
            return $this->data[$k];
        }
        return $default;
    }

    protected final function set($k, $v) {
        if ($k !== null) {
            $this->data[$k] = $v;
        }
    }

    protected final function remove() {
        $argc = func_get_args();
        if ($argc != null && sizeof($argc)) {
            foreach ($argc as $k) {
                if (isset($this->data[$k])) {
                    unset($this->data[$k]);
                }
            }
        }
    }

    protected final function has($k): bool {
        return $k !== null && isset($this->data[$k]);
    }

    protected final function is($k, $v): bool {
        return $k !== null && isset($this->data[$k]) && $this->data[$k] === $v;
    }

    public final function first() {
        if (sizeof($this->data)) {
            return array_values($this->data)[0];
        }
        return null;
    }

    public final function last() {
        if (sizeof($this->data)) {
            $arr = array_values($this->data);
            return end($arr);
        }
        return null;
    }

    public final function jsonSerialize()
    {
        return $this->data;
    }

    public final function count(): int
    {
        return sizeof($this->data);
    }

    public final function hasElement(): bool {
        return $this->count() > 0;
    }

    public final function isEmpty(): bool {
        return !$this->hasElement();
    }

    public final function getAsArray(): array {
        return $this->data;
    }
}