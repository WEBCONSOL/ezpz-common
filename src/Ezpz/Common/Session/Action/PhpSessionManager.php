<?php

declare(strict_types=1);

namespace Ezpz\Common\Session\Action;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use \Ezpz\Common\Session\Domain\PhpSession;
use WC\Models\ListModel;

class PhpSessionManager
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var int
     */
    private $now = 0;

    /**
     * @var array
     */
    private $session = array();

    /**
     * PhpSessionManager constructor.
     *
     * @param EntityManager $em
     * @param string        $id
     */
    public function __construct(EntityManager $em, string $id = "")
    {
        $this->em = $em;
        $this->now = strtotime('now');
        $this->id = $id;
        $this->start();
        $this->gc();
    }

    /**
     * @param $path
     * @param $name
     *
     * @return bool
     */
    public function open($path, $name) {return ($this->em instanceof EntityManager);}

    /**
     * @return bool
     */
    public function close(){$this->gc();return true;}

    /**
     * @param $id
     *
     * @return array
     */
    public function read($id): array
    {
        if ($this->isValidId($id))
        {
            $phpSession = $this->em->getRepository(PhpSession::class)->findOneBy(array('session_id' => $id));

            if ($phpSession instanceof PhpSession)
            {
                $this->session = $this->fromValue($phpSession->getData());
                return $this->session;
            }
        }
        return array();
    }

    /**
     * @param string $id
     * @param mixed  $data
     * @param int    $userId
     *
     * @return bool|mixed
     */
    public function write(string $id, $data, int $userId=0)
    {
        if ($this->isValidId($id) && !empty($data))
        {
            $data = $this->unserialize($data);
            $userId = $this->get(SESSION_KEY_LOGON_USER_ID, $userId);

            if (!empty($this->read($id)))
            {
                $this->em->createQueryBuilder()
                    ->update(PhpSession::class, 't')
                    ->set('t.session_data', ':data')
                    ->set('t.date_created', strtotime('now'))
                    ->set('t.last_updated', strtotime('now'))
                    ->set('t.user_id', ':uid')
                    ->where('t.session_id = :id')
                    ->setParameter('id', $id)
                    ->setParameter('data', $this->toValue($data))
                    ->setParameter('uid', $userId)
                    ->getQuery()
                    ->execute();
            }
            else
            {
                $session = new ListModel(array(
                    'session_id' => $id,
                    'session_data' => $this->toValue($data),
                    'date_created' => strtotime('now'),
                    'last_updated' => strtotime('now')
                ));
                if (!empty($userId))
                {
                    $session->set('user_id', $userId);
                }
                $newRandomPhpSession = new PhpSession($session);
                $this->em->persist($newRandomPhpSession);
                try {
                    $this->em->flush();
                }
                catch (OptimisticLockException $e) {
                    new \Exception($e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * @param $id
     *
     * @return bool|mixed
     */
    public function destroy($id)
    {
        if ($this->isValidId($id))
        {
            if (session_status() !== PHP_SESSION_ACTIVE)
            {
                session_destroy();
            }

            $this->em->createQueryBuilder()
                ->delete(PhpSession::class, 't')
                ->where('t.session_id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->execute();
        }

        return true;
    }

    /**
     * void
     */
    public function gc()
    {
        $this->em->createQueryBuilder()
            ->delete(PhpSession::class, 't')
            ->where('t.last_updated < :last_updated')
            ->setParameter('last_updated', ($this->now - SESSION_LIFETIME))
            ->getQuery()
            ->execute();
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getUserSession(int $id): array
    {
        $phpSession = $this->em->getRepository(PhpSession::class)->findOneBy(array('user_id' => $id));

        if ($phpSession instanceof PhpSession)
        {
            return $phpSession->jsonSerialize();
        }

        return array();
    }

    /**
     * @return string
     */
    public function getId() {return session_id();}

    /**
     * @param $k
     *
     * @return bool
     */
    public function has($k): bool {return (is_array($_SESSION)&&isset($_SESSION[$k]))||(isset($this->session[$k]));}

    /**
     * @param string $k
     * @param string $default
     *
     * @return mixed|string
     */
    public function get(string $k, $default = "")
    {
        if (isset($this->session[$k])) {
            return $this->session[$k];
        }
        if (is_array($_SESSION) && isset($_SESSION[$k])) {
            return $_SESSION[$k];
        }
        return $default;
    }

    /**
     * @param string $k
     * @param        $v
     */
    public function set(string $k, $v)
    {
        $this->session[$k] = $this->toValue($v);
        $_SESSION[$k] = $this->session[$k];
    }


    /**
     * @param string $id
     * @param array  $data
     * @param int    $userId
     */
    public function restart(string $id, array $data, $userId=0)
    {
        if (session_status() === PHP_SESSION_ACTIVE && $id != session_id())
        {
            $this->destroy(session_id());
        }
        if (!session_id($id))
        {
            session_start();
        }
        if (session_status() !== PHP_SESSION_ACTIVE)
        {
            new \Exception("Session restart, but session is still not active.");
        }

        if (!empty($data))
        {
            $data = $this->fromValue($this->toValue($data));

            foreach ($data as $k=>$v)
            {
                $this->set($k, $v);
            }

            if ($userId > 0 && !$this->has(SESSION_KEY_LOGON_USER_ID))
            {
                $this->set(SESSION_KEY_LOGON_USER_ID, $userId);
            }
        }
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    public function unserialize(string $session_data): array
    {
        $output = array();
        $method = ini_get("session.serialize_handler");

        switch ($method)
        {
            case "php":
                $output = $this->unserialize_php($session_data);
                break;

            case "php_binary":
                $output = $this->unserialize_phpbinary($session_data);
                break;

            default:
                new \Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }

        return $output;
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    private function unserialize_php(string $session_data): array
    {
        $return_data = array();
        $offset = 0;

        while ($offset < strlen($session_data))
        {
            if (!strstr(substr($session_data, $offset), "|")) {
                new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $num = $pos - $offset;
            $varname = substr($session_data, $offset, $num);
            $offset += $num + 1;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }

    /**
     * @param string $session_data
     *
     * @return array
     */
    private function unserialize_phpbinary(string $session_data): array
    {
        $return_data = array();
        $offset = 0;

        while ($offset < strlen($session_data))
        {
            $num = ord($session_data[$offset]);
            $offset += 1;
            $varname = substr($session_data, $offset, $num);
            $offset += $num;
            $data = unserialize(substr($session_data, $offset));
            $return_data[$varname] = $data;
            $offset += strlen(serialize($data));
        }

        return $return_data;
    }


    /**
     * void
     */
    private function start()
    {
        session_set_save_handler (
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        );

        register_shutdown_function('session_write_close');

        if (!empty($this->id)) {
            if (!session_id($this->id)) {
                session_start();
            }
        }
        else if (!session_id()) {
            session_start();
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            new \Exception("Session start, but session is still not active.");
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    private function isValidId(string $id): bool{return !empty($id) ? preg_match('/^[-,a-zA-Z0-9]{1,32}$/', $id) > 0 : false;}

    /**
     * @param string $val
     *
     * @return array
     */
    public function fromValue(string $val): array
    {
        if (is_object($val)) {
            return json_decode(json_encode($val), true);
        }
        if (is_array($val)) {
            return $val;
        }
        if (empty($val)) {
            return array();
        }
        $json = json_decode($val, true);
        return is_array($json) ? $json : ($val ? array($val) : array());
    }

    /**
     * @param mixed $val
     *
     * @return string
     */
    public function toValue($val): string
    {
        if (is_array($val) || is_object($val)) {
            return json_encode($val);
        }
        if (is_numeric($val)) {
            return "".$val;
        }
        return $val;
    }
}