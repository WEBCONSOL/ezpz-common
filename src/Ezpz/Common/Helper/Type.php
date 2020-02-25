<?php

namespace Ezpz\Common\Helper;

final class Type
{
    private function __construct(){}

    public static $SUBSCRIPTION = ['basic'=>'basic', 'silver'=>'silver', 'gold'=>'gold'];
    public static $DURATION = ['1_month'=>'1_month', '3_month'=>'3_month', '6_month'=>'6_month', '1_year'=>'1_year'];
    public static $COUPON = ['date'=>'date', 'category'=>'category', 'product'=>'product', 'store'=>'store'];
    public static $OFFER = ['discount'=>'discount', 'bundle'=>'bundle', 'crosssell'=>'crosssell', 'upsell'=>'upsell'];
}