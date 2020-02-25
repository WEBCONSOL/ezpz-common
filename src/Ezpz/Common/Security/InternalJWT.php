<?php

namespace Ezpz\Common\WC\Security;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha384;
use Lcobucci\JWT\ValidationData;
use WC\Utilities\Logger;

final class InternalJWT {

    private static $issuer = 'EzpizeeServer';
    private static $audience = 'api.ezpizee.com:server';
    private static $key_data = 'content';

    private function __construct(){}

    public static function encrypt(string $data): string {
        $signer = new Sha384();
        return ((new Builder())
            ->setIssuer(self::$issuer)
            ->setHeader('alg', 'HS384')
            ->setHeader('typ', 'JWT')
            ->setId(self::$audience, true)
            ->setAudience(self::$audience)
            ->set(self::$key_data, $data)
            ->sign($signer, self::secret())
            ->getToken()).'';
    }

    public static function decrypt(string $token): string {
        try {
            $token = (new Parser())->parse($token);
            $data = new ValidationData();
            $data->setId(self::$audience);
            $data->setIssuer(self::$issuer);
            $data->setAudience(self::$audience);
            $signer = new Sha384();
            if ($token->validate($data) && $token->verify($signer, self::secret())) {
                return $token->getClaim(self::$key_data, '');
            }
        }
        catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
        return '';
    }

    private static function secret(): string {
        return 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC/x9LZ+1marjRyhpUjms+Sajk10DecHT1NE3nlEiP4IebpVOTXtUp+QHUdo9ifU9tFBXJfB8gbPGZ6vVq9br6lblaGh8y7Or6I9y9vJN0Nd8RD3Eobl4Oatu4qhipN+xqyF4ut066xts8uL3Q64pmZhO87/kEx7DtJrHCrHmGfPUXnVBThgjvnRmLblnQvWtCfoDAaIhIljwPEeUmRA/bLPH3lODaZ/b7/3iSUhxePD/Cnf74NClVLknERhjBpnZoltSWdQqwv0haZfG5s52St1WFWj3Slw24QQCKcVMqLCzTKPxUsZXz+Sfip8WZCnqAMQZoiaD3hvLTweTtZJo53 snim@Sotheas-MBP';
    }
}