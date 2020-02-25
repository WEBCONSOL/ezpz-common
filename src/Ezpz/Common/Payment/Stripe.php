<?php

namespace Ezpz\Common\Payment;

use Stripe\Oauth;
use Stripe\Stripe as EzpzStripe ;
use Stripe\Token;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Refund;

use Payment\Helpers\JSONObject;
use Payment\Models\OrderItem;
use Payment\Models\OrderItems;
use Payment\Models\CreditCard as EzpzCreditCard;
use Payment\Models\PaymentTypes;
use Payment\Models\StripePaymentApiKey;
use Payment\Services\PaymentServiceInterface;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;

class Stripe implements PaymentServiceInterface
{
    const ID = "stripe";
    const NAME = "Stripe";

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
        $this->apiKey = new StripePaymentApiKey($paymentApiKey);
    }

    public function setConfiguration(JSONObject $paymentApiKey, $accessToken) {
        $this->loadApiContext($paymentApiKey);
        if (!empty($accessToken) && isset($accessToken)) {
            $stripe = new EzpzStripe();
            $stripe->setApiKey($this->getApiKey()->getSecretKey());
        }
    }

    public function getApiKey() {
        if ($this->apiKey instanceof StripePaymentApiKey) {
            return $this->apiKey;
        }
        return (new StripePaymentApiKey(new JSONObject()));
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

            // add customer to stripe
            $customer = Customer::create(array(
                'email' => $cardModel->getEmail(),
                'source'  => $cardModel->getCardToken()
            ));

            // Multiply 100 for cents with stripe
            // amount has integer type.
            // ex) $321.45 --> 32145
            $amount  = $amount * 100 ;
            // charge
            $results = Charge::create(array(
                'customer' => $customer->id,
                'amount'   => $amount,
                'currency' => $this->currency));
            // print_r($results); exit;

            return $results;
        }
        catch (\Exception $e) {
            Logger::error($e->getMessage());
            CustomResponse::render($e->getCode(), $e->getMessage(), false, []);
        }
        return null;
    }

    public function refund($chargeId, $amount) {
        try {
            $stripe = new EzpzStripe();
            $stripe->setApiKey($this->getApiKey()->getSecretKey());

            // Multiply 100 for cents with stripe
            // amount has integer type.
            // ex) $321.45 --> 32145
            $amount  = $amount * 100 ;

            $results = Refund::create([
                'charge' => $chargeId,
                'amount' => $amount,
            ]);
            // print_r($results); exit;

            return $results;

        } catch (\Exception $e) {
            Logger::error($e->getMessage());
            CustomResponse::render($e->getCode(), $e->getMessage(), false, []);
        }
        return null;
    }

}
