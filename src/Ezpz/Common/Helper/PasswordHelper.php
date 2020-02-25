<?php

namespace Ezpz\Common\Helper;

use WC\Utilities\CustomResponse;

final class PasswordHelper
{
    private function __construct(){}

    public static function encrypt(string $pwd): string {
        if ($pwd) {
            return password_hash(self::pair($pwd), PASSWORD_BCRYPT);
        }
        else {
            CustomResponse::render(500, 'Password cannot be blank.');
        }
        return "";
    }

    public static function verify(string $pwd, string $hashedPwd): bool {
        if ($pwd && $hashedPwd) {
            return password_verify(self::pair($pwd), $hashedPwd);
        }
        else {
            CustomResponse::render(500, 'Password and hashed password cannot be blank.');
        }
        return false;
    }

    private static function pair(string $pwd): string {return EZPZ_APP_SALT.":".$pwd;}
}