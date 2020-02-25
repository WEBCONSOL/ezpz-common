<?php

namespace Ezpz\Common\Payment;

use PayPal\Rest\ApiContext;
use PayPal\Api\Amount;
use PayPal\Api\CreditCard;
use PayPal\Api\FundingInstrument;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\Transaction;
use PayPal\Api\CreditCardToken;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;
use Payment\Helpers\JSONObject;
use Payment\Models\OrderItem;
use Payment\Models\OrderItems;
use Payment\Models\CreditCard as EzpzCreditCard;
use Payment\Models\PaymentTypes;
use Payment\Models\PayPalPaymentApiKey;
use Payment\Services\PaymentServiceInterface;
use WC\Utilities\CustomResponse;
use WC\Utilities\Logger;

class PayPal implements PaymentServiceInterface
{
    const ID = "paypal";
    const NAME = "PayPal";

    private $apiKey;
    private $checkoutType;
    private $paymentType;
    private $apiContext;

    private $shipping_cost;

    private $currency = '';
    private $orderId;
    private $storeId;

    private $intent = 'sale';

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
        $this->apiKey = new PayPalPaymentApiKey($paymentApiKey);
        if (!$this->apiContext && $this->getApiKey()->getClientId() && $this->getApiKey()->getClientSecret()) {
            $oAuthTokenCredential = new OAuthTokenCredential($this->getApiKey()->getClientId(), $this->getApiKey()->getClientSecret()) ;
            $this->apiContext = new ApiContext($oAuthTokenCredential);
        }
    }

    public function getApiKey() {
        if ($this->apiKey instanceof PayPalPaymentApiKey) {
            return $this->apiKey;
        }
        return (new PayPalPaymentApiKey(new JSONObject()));
    }

    public function pay(EzpzCreditCard $cardModel, OrderItems $orderItems)
    {
        $payment = null;
        if ($this->apiContext)
        {
            switch ($this->paymentType)
            {
                case PaymentTypes::CREDIT_CARD:
                    if ($cardModel->isValid()) {
                        $payment = $this->directCreditCardPayment($cardModel, $orderItems);
                    }
                    break;
            }
        }
        return $payment ;
    }

    public function createCreditCard(EzpzCreditCard $cardModel)
    {
        if ($cardModel->isValid() && $this->apiContext) {
            $creditCard = new CreditCard();
            $creditCard->setType($cardModel->getType());
            $creditCard->setNumber($cardModel->getNumber());
            $creditCard->setExpireMonth($cardModel->getExpireMonth());
            $creditCard->setExpireYear($cardModel->getExpireYear());
            $creditCard->setCvv2($cardModel->getCvv2());
            $creditCard->setFirstName($cardModel->getFirstName());
            $creditCard->setLastName($cardModel->getLastName());
            $results = (array) $creditCard->create($this->apiContext);
            // print_r(json_decode($results)); exit;
            if (!empty($results) && sizeof($results) > 0) {
                foreach ($results as $i => $result) {
                    $cardModel->setCreditCardId($result['id']) ;
                }
            }
        }
    }

    private function directCreditCardPayment(EzpzCreditCard $cardModel, OrderItems $orderItems) {
        try {
            //$this->pushPurchase(new JSONObject(''));
            // ### PaymentCard
            // A resource representing a payment card that can be
            // used to fund a payment.
            $credit_card_token = new CreditCardToken();
            $credit_card_token->setCreditCardId($cardModel->getCreditCardId());

            // ### FundingInstrument
            // A resource representing a Payer's funding instrument.
            // For direct credit card payments, set the CreditCard
            // field on this object.
            $fundinginstrument = new FundingInstrument();
            $fundinginstrument->setCreditCardToken($credit_card_token);

            // ### Payer
            // A resource representing a Payer that funds a payment
            // For direct credit card payments, set payment method
            // to 'credit_card' and add an array of funding instruments.
            $payer = new Payer();
            $payer->setPaymentMethod($this->getApiKey()->getPaymentType());
            $payer->setFundingInstruments(array($fundinginstrument));

            $total = (float)$orderItems->getTotal();
            foreach ($orderItems->getItems() as $orderItem) {
                if ($orderItem instanceof OrderItem) {
                    if (!$this->currency) {
                        $this->currency = $orderItem->getCurrencyCode();
                        break ;
                    }
                }
            }

            if($this->getShipping() > 0.0) {
                $total = $total + (float)$this->getShipping();
            }

            // ### Amount
            // Lets you specify a payment amount.
            // You can also specify additional details
            // such as shipping, tax.
            $amount = new Amount();
            $amount->setCurrency($this->currency);
            $amount->setTotal($total);

            // ### Transaction
            // A transaction defines the contract of a
            // payment - what is the payment for and who
            // is fulfilling it.
            $transaction = new Transaction();
            $transaction->setAmount($amount);
            $transaction->setInvoiceNumber(uniqid());

            // ### Payment
            // A Payment Resource; create one using
            // the above types and intent set to sale 'sale'
            $payment = new Payment();
            $payment->setIntent($this->intent);
            $payment->setPayer($payer);
            $payment->setTransactions(array($transaction));

            // print_r(json_decode($payment)); exit;
            $results = $payment->create($this->apiContext);
            // print_r(json_decode($results)); exit;

            return $results;

        } catch (\PayPal\Exception\PayPalConnectionException $e) {
            Logger::error($e->getData());
            CustomResponse::render($e->getCode(), $e->getData(), false, []);
        }

        return null;
    }

    public function refund($saleId, $currency, $refundMoney) {
        try {
            $amount = new Amount();
            $amount->setCurrency($currency);
            $amount->setTotal($refundMoney);

            $refundRequest = new RefundRequest();
            $refundRequest->setAmount($amount);

            $sale = new Sale();
            $sale->setId($saleId);

            $results = $sale->refundSale($refundRequest, $this->apiContext);
            // print_r(json_decode($results)); exit;

            return $results;

        } catch (\PayPal\Exception\PayPalConnectionException $e) {
            Logger::error($e->getData());
            CustomResponse::render($e->getCode(), $e->getData(), false, []);
        }
        return null;
    }
}