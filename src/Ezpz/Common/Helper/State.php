<?php

namespace Ezpz\Common\Helper;

final class State
{
    private function __construct(){}

    public static $ORDER = ['in_cart'=>'in_cart', 'incomplete'=>'incomplete', 'complete'=>'complete'];
    public static $SHIPPING = ['packaging'=>'packaging', 'shipping'=>'shipping', 'delivered'=>'delivered'];
    public static $PAYMENT = ['paid'=>'paid', 'refund'=>'refund', 'canceled'=>'canceled', 'rejected'=>'rejected'];
}