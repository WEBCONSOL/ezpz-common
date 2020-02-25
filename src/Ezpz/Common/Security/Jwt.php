<?php

namespace Ezpz\Common\WC\Security;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\ValidationData;
use WC\Utilities\EncodingUtil;

final class Jwt {

    const HEADER_TOKEN_NAME = 'JWT-Token';
    private static $ALG = 'HS256';
    private static $TYPE = 'JWT';
    private static $clientIssSfx = 'api.ezpizee.com:client';
    private static $clientAudSfx = 'EzpizeeServer';
    private static $sshGenJtiSfx = 'EzpizeeApp';
    private static $serverIssSfx = 'api.ezpizee.com:server';

    private function __construct(){}

    public static function encryptData($data): string {
        if (is_array($data)) {
            $data = implode(':', $data);
        }
        if (is_string($data) && $data) {
            $signer = new Sha256();
            return (new Builder())
                ->setIssuer(EZPZ_ENV.':'.self::$serverIssSfx)
                ->setHeader('alg', self::$ALG)
                ->setHeader('typ', self::$TYPE)
                ->setAudience(md5(EZPZ_ENV.':'.self::$clientIssSfx))
                ->set('client', base64_encode($data))
                ->sign($signer, self::ecdsaKey())
                ->getToken();
        }
        return "";
    }

    /**
     * To be used by Ezpizee Server
     *
     * @param Token $token
     * @param string $appName
     *
     * @return bool true if the token is valid; otherwise, false.
     */
    public static function verifyClientRequestToken(Token $token, string $appName): bool {
        if ($token->token && strlen($token->access_token) === 40) {
            $requestDataToken = (new Parser())->parse($token->token);
            $data = new ValidationData();
            $data->setId(EZPZ_ENV.':'.$appName);
            $data->setIssuer(EZPZ_ENV.':'.self::$clientIssSfx);
            $data->setAudience(md5(EZPZ_ENV.':'.self::$clientAudSfx));
            // validate if basic identity data is valid
            $signer = new Sha256();
            return $requestDataToken->validate($data) && $requestDataToken->verify($signer, self::ecdsaKey());
        }
        return false;
    }

    /**
     * To be used by Ezpizee Server
     *
     * @param Token $token
     * @param string $privateKey
     * @param string $appName
     *
     * @return bool true if the token is valid; otherwise, false.
     */
    public static function verifyClientTokenForAccessTokenRequest(Token $token, string $privateKey, string $appName): bool {
        if ($privateKey && $token->token) {
            $requestDataToken = (new Parser())->parse($token->token);
            $data = new ValidationData();
            $data->setId(EZPZ_ENV.':'.$appName);
            $data->setIssuer(EZPZ_ENV.':'.self::$clientIssSfx);
            $data->setAudience(md5(EZPZ_ENV.':'.self::$clientAudSfx));

            // validate if basic identity data is valid
            $signer = new Sha256();
            if (strlen($token->client['phrase']) > 0 && $requestDataToken->validate($data) && $requestDataToken->verify($signer, self::ecdsaKey())) {
                $keyChain = new OpenSSLKeyChain();
                $keyChain->key = $privateKey;
                $keyChain->cert = $token->ssh;
                $keyChain->phrase = $token->client['phrase'];
                // validate if ssh key is valid
                return SSHAgent::verifyKey($keyChain);
            }
        }
        return false;
    }

    public static function verifySSHGenFormData(SSHGenToken $token) {
        $requestDataToken = (new Parser())->parse($token->token);
        $data = new ValidationData();
        $data->setId(EZPZ_ENV.':'.self::$sshGenJtiSfx);
        $data->setIssuer(EZPZ_ENV.':'.self::$clientIssSfx);
        $data->setAudience(md5(EZPZ_ENV.':'.self::$clientAudSfx));
        // validate if basic identity data is valid
        $signer = new Sha256();
        return $requestDataToken->validate($data) && $requestDataToken->verify($signer, self::ecdsaKey());
    }

    /**
     * @param string $tokenData
     *
     * @return SSHGenToken
     */
    public static function decryptSSHGenFormData(string $tokenData): SSHGenToken {
        $token = (new Parser())->parse($tokenData);
        $decryptedToken = new SSHGenToken();
        $decryptedToken->token = $tokenData;
        $decryptedToken->jti = $token->getHeader('jti', $token->getClaim('jti', ''));
        $decryptedToken->issuer = $token->getClaim('iss', '');
        $decryptedToken->audience = $token->getClaim('aud', '');
        $decryptedToken->phrase = $token->getClaim('phrase', '');
        $decryptedToken->client_id = $token->getClaim('client_id', '');
        $decryptedToken->app_name = $token->getClaim('app_name', '');
        return $decryptedToken;
    }

    /**
     * @param string $tokenData
     *
     * @return Token
     */
    public static function decryptToken(string $tokenData): Token {
        $token = (new Parser())->parse($tokenData);
        $decryptedToken = new Token();
        $decryptedToken->token = $tokenData;
        $decryptedToken->jti = $token->getHeader('jti', $token->getClaim('jti', ''));
        $jti = explode(':', $decryptedToken->jti);
        if (sizeof($jti) === 2) {
            $decryptedToken->env = $jti[0];
            $decryptedToken->appName = $jti[1];
        }
        $decryptedToken->issuer = $token->getClaim('iss', '');
        $decryptedToken->audience = $token->getClaim('aud', '');
        $decryptedToken->ssh = $token->getClaim('ssh', '');
        $decryptedToken->access_token = $token->getClaim('access_token', '');
        $client = $token->getClaim('client', '');
        if ($client && EncodingUtil::isBase64Encoded($client)) {
            $client = explode(':', base64_decode($client));
            if (sizeof($client) > 1) {
                if (strlen($client[0]) === 32) {
                    $decryptedToken->client['client_id'] = $client[0];
                    if (strlen($client[1]) === 32) {
                        $decryptedToken->client['client_secret'] = $client[1];
                        if (isset($client[2])) {
                            $decryptedToken->client['phrase'] = $client[1];
                        }
                    }
                    else {
                        $decryptedToken->client['phrase'] = $client[1];
                    }
                }
            }
        }
        return $decryptedToken;
    }

    private static function ecdsaKey(): string {
        return "ecdsa-sha2-nistp256 AAAAE2VjZHNhLXNoYTItbmlzdHAyNTYAAAAIbmlzdHAyNTYAAABBBJL2OA0xJsYJz+Va+ayzfBqbMsPRy2wIMDbPHSS0xVoTj6Vl+Mcl5WHAmudwhie5k8DnWKssCPJEhUkVY7a7I18= info@webconsol.com";
    }
}