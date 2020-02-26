<?php

namespace Ezpz\Common\Security;

final class SSHAgent
{
    private static $keys;

    /**
     * @var array $dn
     */
    private static $dn = array(
        "countryName" => "CA",
        "stateOrProvinceName" => "QC",
        "localityName" => "Montreal",
        "organizationName" => "WEBCONSOL Inc",
        "organizationalUnitName" => "Ezpizee",
        "commonName" => "ezpizee.com",
        "emailAddress" => "security@ezpizee.com"
    );

    /**
     * @param string $phrase
     *
     * @return OpenSSLKeyChain
     */
    public static function genKeys(string $phrase): OpenSSLKeyChain {
        if (!(self::$keys instanceof OpenSSLKeyChain)) {
            self::$keys = new OpenSSLKeyChain();
            self::$keys->phrase = $phrase;
            $private_key_res = openssl_pkey_new(["private_key_bits" => 2048, "private_key_type" => OPENSSL_KEYTYPE_RSA]);
            $csr_res = openssl_csr_new(self::$dn, $private_key_res, ['digest_alg'=>'sha256']);
            $x509 = openssl_csr_sign($csr_res, null, $private_key_res, $days=365, array('digest_alg' => 'sha256'));
            openssl_csr_export($csr_res, self::$keys->csr);
            openssl_x509_export($x509, self::$keys->cert);
            openssl_pkey_export($private_key_res, self::$keys->key, $phrase);
        }
        return self::$keys;
    }

    /**
     * @param OpenSSLKeyChain $keys
     *
     * @return bool
     */
    public static function verifyKey(OpenSSLKeyChain $keys): bool {return openssl_x509_check_private_key($keys->cert, [$keys->key, $keys->phrase]);}
}