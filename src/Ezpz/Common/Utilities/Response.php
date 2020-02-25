<?php

namespace Ezpz\Common\Utilities;

class Response extends \GuzzleHttp\Psr7\Response
{
    const CODE_OK = 200;
    const CODE_FORBIDDEN = 403;
    const CODE_NOTFOUND = 404;
    const CODE_INTERNAL_SERVER_ERROR = 500;
    const CODE_INVALID_LOGIN_CREDENTIALS = 4004;
    const CODE_INVALID_OAUTH_CREDENTIALS = 5004;
    const MESSAGE_200 = "Successfully processed.";
    const MESSAGE_403 = "Forbidden. You don't have permission to access this resource.";
    const MESSAGE_404 = "Resource Not Found. We cannot find the resource you are requesting.";
    const MESSAGE_500 = "Internal Server Error.";
    private static $DEBUG = false;

    public static function setDebug(bool $isDebug) {
        self::$DEBUG = $isDebug;
    }

    /**
     * @param int        $code
     * @param string     $message
     * @param array|null $data
     */
    public static function renderAsJSON(int $code, string $message, array $data=null)
    {
        $obj = array('success'=>$code===200, 'statusCode'=>$code, 'message'=>$message, 'data'=>$data);
        if (self::$DEBUG) {
            $obj['backtrace'] = debug_backtrace();
        }
        header(HEADER_CONTENTTYPE.CONTENTTYPE_HEADER_JSON);
        //header('Content-Disposition','attachment;filename="'.uniqid('json-file-').'.json"');
        die(json_encode($obj));
    }

    /**
     * @param string $data
     */
    public static function renderJSONString(string $data)
    {
        header(HEADER_CONTENTTYPE.CONTENTTYPE_HEADER_JSON);
        //header('Content-Disposition','attachment;filename="'.uniqid('json-file-').'.json"');
        die($data);
    }

    /**
     * @param string $data
     */
    public static function renderPlaintext(string $data)
    {
        header(HEADER_CONTENTTYPE.CONTENTTYPE_HEADER_PLAINTEXT);
        die($data);
    }

    /**
     * @param $url
     */
    public static function redirect(string $url) {header("Location: " . $url, true, 301);}

    /**
     * @param string $name
     * @param string $valuePrefix
     *
     * @return string
     */
    public static function get($name, $valuePrefix = ''): string {
        $nameLength = strlen($name);
        $valuePrefixLength = strlen($valuePrefix);
        $headers = headers_list();
        foreach ($headers as $header) {
            if (substr($header, 0, $nameLength) === $name) {
                if (substr($header, $nameLength + 2, $valuePrefixLength) === $valuePrefix) {
                    return $header;
                }
            }
        }
        return $valuePrefix;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public static function set(string $name, string $value) {header($name.': '.$value);}

    /**
     * @param string $name
     * @param string $value
     */
    public static function add(string $name, string $value) {header($name.': '.$value, false);}

    /**
     * @param string $name
     * @param string $valuePrefix
     */
    public static function remove(string $name, string $valuePrefix = '') {
        if (empty($valuePrefix)) {
            header_remove($name);
        }
        else {
            $found = self::get($name, $valuePrefix);
            if (isset($found)) {
                header_remove($name);
            }
        }
    }

    /**
     * @param string $name
     * @param string $valuePrefix
     *
     * @return null
     */
    public static function take(string $name, string $valuePrefix = ''): string {
        $found = self::get($name, $valuePrefix);
        if (isset($found)) {
            header_remove($name);
            return $found;
        }
        else {
            return $valuePrefix;
        }
    }
}