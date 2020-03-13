<?php

namespace Ezpz\Common\Helper;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use \Ezpz\Common\Repository\DBTableConstants;
use WC\Utilities\Logger;
use WC\Utilities\StringUtil;

final class UserHelper
{
    /**
     * @var EntityManager $em
     */
    private $em;

    public function __construct(EntityManager $em){$this->em = $em;}

    public function isSuperAdmin($user) {
        $sql = '';
        if (is_numeric($user)) {
            $sql = 'SELECT is_super_admin FROM ' . DBTableConstants::OAUTH_USERS . ' WHERE id="' . $user . '"';
        }
        else if (is_string($user)) {
            if (StringUtil::isEmail($user)) {
                $sql = 'SELECT is_super_admin FROM ' . DBTableConstants::OAUTH_USERS . ' WHERE email="' . $user . '"';
            }
            else {
                $sql = 'SELECT is_super_admin FROM ' . DBTableConstants::OAUTH_USERS . ' WHERE username="' . $user . '"';
            }
        }
        if ($sql) {
            try {
                $row = $this->em->getConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
                if (!empty($row) && isset($row['is_super_admin']) && (int)$row['is_super_admin'] === 1) {
                    return true;
                }
            }
            catch (DBALException $e) {
                Logger::error($e->getMessage());
            }
        }
        return false;
    }

    public function getUserId(string $username): int {
        try {
            $sql = 'SELECT id FROM '.DBTableConstants::OAUTH_USERS.' WHERE username="'.$username.'"';
            $row = $this->em->getConnection()->executeQuery($sql)->fetch(\PDO::FETCH_ASSOC);
            return !empty($row) ? (int)$row['id'] : 0;
        }
        catch (DBALException $e) {
            Logger::error($e->getMessage());
            return 0;
        }
    }
}