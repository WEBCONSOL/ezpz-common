<?php

namespace Ezpz\Common\ApiGateway;

final class Constants
{
    const METHODS = array("get"=>"GET", "post"=>"POST", "delete"=>"DELETE", "put"=>"PUT");
    const PAGINATION_DEFAULT_NUMPERPAGE = 15;
    const PAGINATION_DEFAULT_PAGE = 0;
    const ALLOWED_HEADER_PARAMS = array(
        "Origin", "Referer"
        ,HEADER_AUTHORIZATION, HEADER_ACCESS_TOKEN, HEADER_CLIENT_ID, HEADER_CLIENT_SECRET, HEADER_USER_ID, HEADER_CSRFTOKEN
        ,"ids", "list", "parent_id", "user_id", "editid", "edit_id"
    );
    const API_ACTIVE_VERSIONS = ['v1'];
    const PRODUCT_ENTITIES = [
        'store'=>'store',
        'category'=>'category',
        'country'=>'country',
        'currency'=>'currency'
    ];
    const PRODUCT_TYPE_FIELDS = ['b.id','b.alias','b.name','b.description','b.product_attrs'];

    private function __construct(){}
}