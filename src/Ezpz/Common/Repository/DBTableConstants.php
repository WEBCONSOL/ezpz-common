<?php

namespace Ezpz\Common\Repository;

class DBTableConstants
{
    private function __construct(){}

    const SESSIONS = "sessions";
    const PHP_SESSION = "php_session";

    const OAUTH_USERS = 'oauth_users';
    const OAUTH_CLIENTS = 'oauth_clients';
    const OAUTH_ACCESS_TOKENS = 'oauth_access_tokens';
    const OAUTH_USER_ACTIVATE_TOKEN = 'oauth_user_activate_token';
    const OAUTH_PUBLIC_KEYS = 'oauth_public_keys';

    const SERVERS = 'servers';
    const USERCONFIG = 'userconfig';

    const DA_ASSETS = "da_assets";
    const DA_ASSETS_FILES = "da_assets_files";
    const COMMON_COUNTRIES = "common_countries";
    const COMMON_CURRENCIES = "common_currencies";
    const COMMON_PAYMENTSERVICES = "common_paymentservices";
    const CONTACTS = "contacts";
    const ECOM_EMPLOYEES = "ecom_employees";
    const ECOM_GLOBALPROPERTIES = "ecom_globalproperties";
    const ECOM_STORES = "ecom_stores";
    const ECOM_STORE_TAXES = "ecom_store_taxes";
    const ECOM_CATEGORIES = "ecom_categories";
    const ECOM_MANUFACTURERS = "ecom_manufacturers";
    const ECOM_PAYMENTMETHODS = "ecom_paymentmethods";
    const ECOM_TAXES = "ecom_taxes";
    const ECOM_SHIPPINGMETHODS = "ecom_shippingmethods";
    const ECOM_PRODUCT_ATTRS = "ecom_product_attrs";
    const ECOM_PRODUCT_TYPES = "ecom_product_types";
    const ECOM_PRODUCTS = "ecom_products";
    const ECOM_PRODUCT_ENTITIES = "ecom_product_entities";
    const ECOM_STORE_ENTITIES = "ecom_store_entities";
    const ECOM_PRODUCT_VALUES = "ecom_product_values";
    const ECOM_OFFERS = "ecom_offers";
    const ECOM_OFFER_PRODUCT = "ecom_offer_product";

    const ECOM_CARTS = "ecom_carts";
    const ECOM_ORDERS = "ecom_orders";
    const ECOM_ORDER_ITEMS = "ecom_order_items";
    const ECOM_ORDER_HISTORY = "ecom_order_history";
    const ECOM_CUSTOMERS = "ecom_customers";
    const ECOM_COUPONS = "ecom_coupons";
    const ECOM_ORDER_COUPONS = "ecom_order_coupons";
    const ECOM_PAYMENTS = "ecom_payments";
    const ECOM_REFUND = "ecom_refund";
    const ECOM_INVOICES = "ecom_invoices";
    const ECOM_FULLFILMENT_SHIPPING = "ecom_fullfilment_shipping";
    const ECOM_SUBSCRIPTIONS = "ecom_subscriptions";

}