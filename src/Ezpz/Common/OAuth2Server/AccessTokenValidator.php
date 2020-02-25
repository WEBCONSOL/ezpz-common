<?php

namespace Ezpz\Common\OAuth2Server;

use Ezpz\Common\ApiGateway\Endpoints;
use WC\Security\Jwt;
use WC\Utilities\CustomResponse;
use Slim\App;
use Pimple\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Utilities\HostNames;
use Utilities\HttpClient;
use WC\Utilities\Logger;

class AccessTokenValidator
{
    private static $appName = '';
    private static $accessToken = '';

    public static function getAppName(): string {return self::$appName;}
    public static function getAccessToken(): string {return self::$accessToken;}

    /**
     * @param Request   $request
     * @param Response  $response
     * @param Container $cnt
     *
     * @return bool
     */
    public static function validateWithRequestResponse(Request &$request, Response &$response, Container $cnt): bool
    {
        $valid = false;
        $token = $request->getHeaderLine(HEADER_ACCESS_TOKEN);
        self::processToken($token);
        if (!self::$appName) {self::$appName = $request->getHeaderLine(HEADER_APP_NAME);}
        if (POSTMAN_MODE && self::$appName === 'Ezpizee Postman') {self::$appName = null;}

        if (!empty(self::$accessToken))
        {
            $subRequest = false;
            self::$accessToken = is_array(self::$accessToken) ? self::$accessToken[0] : self::$accessToken;
            $valid = self::_validate($cnt, $subRequest);
            if ($subRequest) {$response = new $response;}
        }
        else
        {
            CustomResponse::render(500, "Access token is required and missing.");
        }

        return $valid;
    }

    /**
     * @param Container $cnt
     *
     * @return bool
     */
    public static function validate(Container $cnt): bool {

        $valid = false;
        $request = new \Utilities\Request();
        $token = $request->getHeaderParam(HEADER_ACCESS_TOKEN);
        self::processToken($token);
        if (!self::$appName) {self::$appName = $request->getHeaderLine('App-Name');}
        if (POSTMAN_MODE && self::$appName === 'Ezpizee Postman') {self::$appName = null;}

        if (!empty(self::$accessToken))
        {
            $valid = self::_validate($cnt, false);
        }
        else
        {
            CustomResponse::render(500, "missing_token");
        }

        if (!$valid) {
            Logger::error('invalid_token. '.json_encode(['access_token'=>self::$accessToken,'app_name'=>self::$appName]));
        }

        return $valid;
    }

    /**
     * @param Container $cnt
     * @param bool      $isSubRequest
     *
     * @return bool
     */
    private static function _validate(Container $cnt, bool $isSubRequest): bool {

        $invalid = true;
        $uri = Endpoints::auth('validateToken', array('token' => self::$accessToken.(self::$appName?':'.self::$appName:'')));
        if ($isSubRequest) {
            Logger::info('Internal '.strtoupper($uri[0]).' API Sub-Request Call '.$uri[1]);
            $app = new App($cnt);
            $subReqResponse = $app->subRequest($uri[0], $uri[1], '', $app->getContainer()->get('request')->getHeaders());
            $json = json_decode($subReqResponse->getBody());
        }
        else {
            $options = array();
            if (EZPZ_USERNAME) {$options['headers'] = array(HEADER_USER_NAME => EZPZ_USERNAME);}
            $url = HostNames::getAuth() . $uri[1];
            $subReqResponse = HttpClient::internalRequest($uri[0], $url, $options);
            $json = json_decode($subReqResponse->getBody());
        }

        if (is_object($json) && isset($json->{RESPONSE_KEY_STATUS_CODE}) && $json->{RESPONSE_KEY_STATUS_CODE} == 200)
        {
            $data = isset($json->{RESPONSE_KEY_DATA}) ? $json->{RESPONSE_KEY_DATA} : null;
            if ($data !== null && isset($data->access_token) && $data->access_token === self::$accessToken)
            {
                $invalid = false;
            }
        }

        if ($invalid)
        {
            CustomResponse::render(500, isset($json->message) ? $json->message : "missing_token");
        }

        return !$invalid;
    }

    private static function processToken(string $token) {
        if ((!POSTMAN_MODE && $token) || strlen($token) > 40) {
            $jwtToken = Jwt::decryptToken($token);
            if (!Jwt::verifyClientRequestToken($jwtToken, $jwtToken->appName)) {
                CustomResponse::render(500, 'Invalid JWT Token from Client');
            }
            self::$accessToken = $jwtToken->access_token;
            self::$appName = $jwtToken->appName;
        }
        else if (POSTMAN_MODE) {
            self::$accessToken = $token;
        }
    }
}