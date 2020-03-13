<?php

namespace Ezpz\Common\Payment;

use SquareConnect\Configuration;
use SquareConnect\Api\TransactionsApi;
use SquareConnect\ApiException;

use \Ezpz\Common\Payment\Helpers\JSONObject;
use \Ezpz\Common\Payment\Models\OrderItem;
use \Ezpz\Common\Payment\Models\OrderItems;
use \Ezpz\Common\Payment\Models\CreditCard as EzpzCreditCard;
use \Ezpz\Common\Payment\Models\PaymentTypes;
use \Ezpz\Common\Payment\Models\SquarePaymentApiKey;
use \Ezpz\Common\Payment\Services\PaymentServiceInterface;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;

class Square implements PaymentServiceInterface
{
    const ID = "square";
    const NAME = "Square";

    private $apiKey;
    private $checkoutType;
    private $paymentType;
    private $shipping_cost;

    private $currency = '';

    private $orderId;
    private $storeId;

    public function __construct() { }

    public function getId(){ return self::ID; }
    public function getName(){ return self::NAME; }
    public function setStoreId($storeId) { $this->storeId = $storeId; }
    public function setOrderId($orderId) { $this->orderId = $orderId; }
    public function setPaymentType($type) { $this->paymentType = $type; }
    public function setCheckoutType($type) { $this->checkoutType = $type; }

    public function setShipping(float $shipping_cost) { $this->shipping_cost = $shipping_cost; }
    public function getShipping():float {return $this->shipping_cost;}

    public function loadApiContext(JSONObject $paymentApiKey) {
        $this->apiKey = new SquarePaymentApiKey($paymentApiKey);
    }

    public function setConfiguration(JSONObject $paymentApiKey, $accessToken) {
        $this->loadApiContext($paymentApiKey);
        if (!empty($accessToken) && isset($accessToken)) {
            $defaultConfiguration = Configuration::getDefaultConfiguration() ;
            // Initialize the authorization for Square
            $defaultConfiguration->setAccessToken($accessToken);
            // Add this right after setAccessToken:
            // localhost testing override ssl
            $defaultConfiguration->setSSLVerification(FALSE);
        }
    }

    public function getApiKey() {
        if ($this->apiKey instanceof SquarePaymentApiKey) {
            return $this->apiKey;
        }
        return (new SquarePaymentApiKey(new JSONObject()));
    }

    public function pay(EzpzCreditCard $cardModel, OrderItems $orderItems)
    {
        $payment = null ;
        switch ($this->paymentType)
        {
            case PaymentTypes::CREDIT_CARD:
                $payment = $this->directCreditCardPayment($cardModel, $orderItems);
                break;
        }
        return $payment ;
    }

    public function createCreditCard(EzpzCreditCard $cardModel) {}

    private function directCreditCardPayment(EzpzCreditCard $cardModel, OrderItems $orderItems) {
        try {
            $amount = (float)$orderItems->getTotal();
            foreach ($orderItems->getItems() as $orderItem) {
                if ($orderItem instanceof OrderItem) {
                    if (!$this->currency) {
                        $this->currency = $orderItem->getCurrencyCode();
                        break ;
                    }
                }
            }
            if ($this->getShipping() > 0.0) {
                $amount = $amount + (float)$this->getShipping();
            }

            // Multiply 100 for cents with stripe
            // amount has integer type.
            // ex) $321.45 --> 32145
            $amount  = $amount * 100 ;

            $transactions_api = new TransactionsApi();

            # To learn more about splitting transactions with additional recipients,
            # see the Transactions API documentation on our [developer site]
            # (https://docs.connect.squareup.com/payments/transactions/overview#mpt-overview).
            $request_body = array (
                "card_nonce" => $cardModel->getCardToken(),
                # Monetary amounts are specified in the smallest unit of the applicable currency.
                # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
                "amount_money" => array (
                    "amount" => $amount,
                    "currency" => $this->currency
                ),
                # Every payment you process with the SDK must have a unique idempotency key.
                # If you're unsure whether a particular payment succeeded, you can reattempt
                # it with the same idempotency key without worrying about double charging
                # the buyer.
                "idempotency_key" => uniqid()
            );

            # The SDK throws an exception if a Connect endpoint responds with anything besides
            # a 200-level HTTP code. This block catches any exceptions that occur from the request.
            $results = $transactions_api->charge($this->getApiKey()->getLocationId(), $request_body);
            // print_r(json_decode($results)); exit;

            return $results;
        }
        catch (\Exception $e) {
            Logger::error($e->getMessage());
            CustomResponse::render($e->getCode(), $e->getMessage(), false, []);
        }

        return null;
    }

    public function refund($tenderId, $locationId, $transactionId, $currency, $amount) {
        try {
            // Multiply 100 for cents with stripe
            // amount has integer type.
            // ex) $321.45 --> 32145
            $amount  = $amount * 100 ;

            $transactions_api = new TransactionsApi();

            $request_body = array (
                # Monetary amounts are specified in the smallest unit of the applicable currency.
                # This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
                "amount_money" => array (
                    "amount" => $amount,
                    "currency" => $currency
                ),
                # Every payment you process with the SDK must have a unique idempotency key.
                # If you're unsure whether a particular payment succeeded, you can reattempt
                # it with the same idempotency key without worrying about double charging
                # the buyer.
                "idempotency_key" => uniqid(),
                "tender_id" => $tenderId
            );

            $results = $transactions_api->createRefund($locationId, $transactionId, $request_body);
            // print_r(json_decode($results)); exit;

            return $results;

        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            CustomResponse::render($e->getCode(), $e->getMessage(), false, []);
        }
        return null;
    }
}
