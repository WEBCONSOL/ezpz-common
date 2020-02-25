<?php

define('EZPZ_COMMERCE_APP', 1);
define('DS', DIRECTORY_SEPARATOR);
define('DATE', 'Y-m-d');
define('DATETIME', 'Y-m-d h:i:s');

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
    die(file_get_contents(__DIR__.DS.'static'.DS.'json'.DS.'403.json'));
}
