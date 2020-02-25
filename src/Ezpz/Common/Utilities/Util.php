<?php

namespace Ezpz\Common\Utilities;

class Util
{
    public static function getUsernameFromSlimRequest(\Slim\Http\Request &$request): string {
        $parts = explode('.', $request->getUri()->getHost());
        $parts = explode('-', $parts[0]);
        if (($parts[0] === 'local' || $parts[0] === 'dev' || $parts[0] === 'qa' || $parts[0] === 'stage') && sizeof($parts) === 3) {
            return $parts[1];
        }
        $username = $request->getHeaderLine(HEADER_USER_NAME);
        if (!empty($username)) {
            return $username;
        }
        $username = $request->getHeaderLine(HEADER_AUTH_USER);
        if (!empty($username)) {
            return $username;
        }
        return "";
    }
}