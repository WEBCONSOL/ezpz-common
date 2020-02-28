<?php

namespace Ezpz\Common\Helper;

use Ezpz\Common\ApiGateway\Constants;
use Ezpz\Common\Utilities\Request;
use \Slim\Http\Request as SlimRequest;

class Query
{
    private static $numPerPage = Constants::PAGINATION_DEFAULT_NUMPERPAGE;
    private static $page = 1;
    private static $numRows = 0;
    private $query = "";

    public function __construct(string $query) {$this->query = $query;}

    public static function getNumPerPage() {return self::$numPerPage;}
    public static function getPage() {return self::$page;}
    public static function getNumRows() {return self::$numRows;}

    public static function loadAll() {self::setNumPerPage('all');}
    public static function setNumPerPage($arg) {self::$numPerPage = $arg;}

    public function getQueryString(): string {return $this->query;}

    public static function queryLimitAndPaginationParams($request): string {
        if (self::$numPerPage !== 'all' && ($request instanceof SlimRequest || $request instanceof Request)) {
            self::$numPerPage = $request->getParam('numPerPage', Constants::PAGINATION_DEFAULT_NUMPERPAGE);
            self::$page = $request->getParam('page', Constants::PAGINATION_DEFAULT_PAGE);
            self::$numRows = $request->getParam('numRows', 0);
            if (self::$page <= 0) {self::$page = 1;}
            $limitStart = ((self::$page - 1) * self::$numPerPage);
            return ' LIMIT ' . $limitStart . ', ' . (self::$numPerPage+1);
        }
        return '';
    }

    public static function appendToResponseResult(array &$list) {
        if (isset($list['data']) && sizeof($list['data']) && isset($list['data'][0])) {
            if (self::$numPerPage !== 'all') {
                $size = sizeof(isset($list['data'])?$list['data']:$list);
                $numRows = $size ? ($size > self::$numPerPage ? $size-1 : $size) : self::$numRows;
                $prev = self::$page - 1;
                $next = $size > self::$numPerPage ? self::$page + 1 : 0;
                $list['pagination'] = [
                    'prev' => $prev < 0 ? 0 : $prev,
                    'current' => self::$page,
                    'next' => $next,
                    'numRows' => $numRows,
                    'numPerPage' => self::$numPerPage
                ];
                if ($next > 0) {
                    unset($list['data'][$size-1]);
                }
            }
            else {
                $list['pagination'] = [
                    'prev' => 0,
                    'current' => self::$page,
                    'next' => 0,
                    'numRows' => self::$numRows,
                    'numPerPage' => self::$numPerPage
                ];
            }
        }
        else if (isset($list['pagination'])) {
            unset($list['pagination']);
        }
    }
}