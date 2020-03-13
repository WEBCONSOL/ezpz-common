<?php

define('EZPZ_COMMERCE_APP', 1);
define('DS', DIRECTORY_SEPARATOR);
define('PATH_COMMON', __DIR__);
define('PATH_COMMON_STATIC', PATH_COMMON . DS . 'static');
define('PATH_COMMON_CONFIG', PATH_COMMON_STATIC . DS . 'config');

define('DATE', 'Y-m-d');
define('DATETIME', 'Y-m-d h:i:s');

define('OAUTH_PUBLIC_KEY', 'file://' . PATH_COMMON_STATIC . '/keys/public.key');
define('OAUTH_PRIVATE_KEY', 'file://' . PATH_COMMON_STATIC . '/keys/private.key');
define('OAUTH_ENCRYPTION_KEY', 'OZdbQCErQWVFLAVw7n2DvSYoEVXIkuOHzgL4kPU8mxw=');
define('SESSION_LIFETIME', 60*60*12); // 12 hours
define('SESSION_KEY_LOGON_USER_ID', 'logon_user_id');
define('SESSION_KEY_USER_DATA', 'user_data');
define('SESSION_KEY_SESSION_DATA', 'session_data');

define('CONTENTTYPE_HEADER_JSON', 'application/json; charset=UTF-8');
define('CONTENTTYPE_HEADER_JS', 'application/javascript; charset=utf-8');
define('CONTENTTYPE_HEADER_CSS', 'text/css; charset=utf-8');
define('CONTENTTYPE_HEADER_PLAINTEXT', 'text/html; charset=utf-8');
define('CONTENTTYPE_HEADER_HTML', 'text/html; charset=utf-8');
define('CONTENTTYPE_HEADER_FORM', 'application/x-www-form-urlencoded; charset=UTF-8');
define('CONTENTTYPE_HEADER_FORM_MULTIPART', 'multipart/form-data');
define('HEADER_CONTENTTYPE', 'Content-Type');
define('HEADER_AUTH_USER', 'PHP_AUTH_USER');
define('HEADER_AUTH_PW', 'PHP_AUTH_PW');
define('HEADER_CSRFTOKEN', 'csrftoken');
define('HEADER_AUTHORIZATION', 'Authorization');
define('HEADER_ACCESS_TOKEN', 'Access-Token');
define('HEADER_CLIENT_ID', 'Client-Id');
define('HEADER_CLIENT_SECRET', 'Client-Secret');
define('HEADER_USER_ID', 'User-Id');
define('HEADER_USER_NAME', 'User-Name');
define('HEADER_DELETE_ID', 'Delete-Id');
define('HEADER_PARENT_ID', 'Parent-Id');
define('HEADER_STORE_ID', 'Store-Id');
define('HEADER_APP_NAME', 'App-Name');
define('HEADER_EMPLOYEE', 'Employee');

define('RESPONSE_KEY_SUCCESS', 'success');
define('RESPONSE_KEY_STATUS_CODE', 'statusCode');
define('RESPONSE_KEY_DATA', 'data');
define('RESPONSE_KEY_MESSAGE', 'message');
define('RESPONSE_KEY_DEBUG', 'debug');

define('COMMON_EXISTS', 'exists');
define('COMMON_NOT_EXISTS', 'not-exists');

define('DB_CONNECTION', 'dbconnection');
define('DB_CONFIG', 'dbconfig');
define('NOT_FOUND_HANDLER', 'notFoundHandler');
define('ERROR_HANDLER', 'errorHandler');
define('NOT_ALLOWED_HANDLER', 'notAllowedHandler');
define('SALT_SFX', 'dtB7uAvT7BjxTWj6XVjUNpq4');
define('EZPZ_LOCAL_MICROSERVICE', true);

define('EZPZ_USER_AGENT', 'Ezpizee/1.0');

define('SENDGRID_API_KEY', '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');
define('SENDGRID_FROM_EMAIL', 'info@webconsol.com');
define('SENDGRID_FROM_NAME', 'Ezpizee Team');

// disable direct access to index.php
if(strpos($_SERVER['REQUEST_URI'], '/index.php') !== false) {
    header(HEADER_CONTENTTYPE.CONTENTTYPE_HEADER_JSON);
    die(file_get_contents(PATH_COMMON_STATIC.DS.'json'.DS.'403.json'));
}

define('HOST_PATTERN_1', '/(.[^-]*)-(.[^-]*)-(.[^.]*).ezpizee.com/');
define('HOST_PATTERN_2', '/(.[^-]*)-(.[^.]*).ezpizee.com/');
define('HOST_PATTERN_3', '/(.[^.]*).ezpizee.com/');
$subject = $_SERVER['HTTP_HOST'];
$matches = array();
$results = array();

$matches = \WC\Utilities\PregUtil::getMatches(HOST_PATTERN_1, $subject);

if (sizeof($matches) === 4 && $matches[0][0] === $subject)
{
    for($i = 1; $i < sizeof($matches); $i++) {
        if (is_array($matches[$i]) && !empty(end($matches[$i]))) {
            $results[] = end($matches[$i]);
        }
        else if (is_string($matches[$i]) && !empty($matches[$i])) {
            $results[] = $matches[$i];
        }
    }
}
else
{
    $matches = \WC\Utilities\PregUtil::getMatches(HOST_PATTERN_2, $subject);
    if (sizeof($matches) === 3 && is_array($matches[0]) && isset($matches[0][0]) && $matches[0][0] === $subject) {
        for($i = 1; $i < sizeof($matches); $i++) {
            if (is_array($matches[$i]) && !empty(end($matches[$i]))) {
                $results[] = end($matches[$i]);
            }
            else if (is_string($matches[$i]) && !empty($matches[$i])) {
                $results[] = $matches[$i];
            }
        }
    }
    else {
        $matches = \WC\Utilities\PregUtil::getMatches(HOST_PATTERN_3, $subject);
        if (sizeof($matches) === 2 && is_array($matches[0]) && isset($matches[0][0]) && $matches[0][0] === $subject) {
            for($i = 1; $i < sizeof($matches); $i++) {
                if (is_array($matches[$i]) && !empty(end($matches[$i]))) {
                    $results[] = end($matches[$i]);
                }
                else if (is_string($matches[$i]) && !empty($matches[$i])) {
                    $results[] = $matches[$i];
                }
            }
        }
    }
}

$sizeOfResults = sizeof($results);

if ($sizeOfResults === 3 && in_array($results[0], \Ezpz\Common\ApiGateway\Env::ENVS))
{
    define('EZPZ_USERNAME', $results[1]);
    define('EZPZ_SITE', $results[2]);
}
else
{
    $req = new \WC\Utilities\Request();

    if ($sizeOfResults === 2 && in_array($results[0], \Ezpz\Common\ApiGateway\Env::ENVS))
    {
        if (in_array($results[0], \Ezpz\Common\ApiGateway\Env::ENVS))
        {
            define('EZPZ_USERNAME', $req->getHeaderParam(HEADER_USER_NAME, ''));
            define('EZPZ_SITE', $results[1]);
        }
        else
        {
            define('EZPZ_USERNAME', $req->getHeaderParam(HEADER_USER_NAME, ''));
            define('EZPZ_SITE', $results[1]);
        }
    }
    else if ($sizeOfResults === 1)
    {
        define('EZPZ_USERNAME', $req->getHeaderParam(HEADER_USER_NAME, ''));
        define('EZPZ_SITE', $results[0]);
    }
    else
    {
        define('EZPZ_USERNAME', '');
        define('EZPZ_SITE', '');
    }
}

unset($subject, $matches, $results);

define('EZPZ_ENV', \Ezpz\Common\Utilities\Envariable::environment());

if (EZPZ_ENV)
{
    define('EZPZ_ENV_IS_LOCAL', EZPZ_ENV === \Ezpz\Common\ApiGateway\Env::ENVS[0]);
    define('EZPZ_ENV_IS_DEV', EZPZ_ENV === \Ezpz\Common\ApiGateway\Env::ENVS[1]);
    define('EZPZ_ENV_IS_QA', EZPZ_ENV === \Ezpz\Common\ApiGateway\Env::ENVS[2]);
    define('EZPZ_ENV_IS_STG', EZPZ_ENV === \Ezpz\Common\ApiGateway\Env::ENVS[3]);
    define('EZPZ_ENV_IS_PROD', EZPZ_ENV === \Ezpz\Common\ApiGateway\Env::ENVS[4]);
    define('SERVER_SCHEMA', 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://');
    define('EZPZ_APP_SALT', md5(SALT_SFX));
}