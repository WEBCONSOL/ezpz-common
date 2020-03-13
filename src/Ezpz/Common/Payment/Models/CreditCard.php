<?php

namespace Ezpz\Common\Payment\Models;

use \Ezpz\Common\Payment\Lib\CreditCardValidator;

class CreditCard
{
    private $type;
    private $number;
    private $expireMonth;
    private $expireYear;
    private $cvv2;
    private $nameOnCard;
    private $firstName;
    private $lastName;
    private $billingCountry = 'US';
    private $valid = false;
    private $errornumber;
    private $errortext;
    private $creditCardId;
    private $cardToken;
    private $email;

    public function __construct(array $creditCard)
    {
        if ($creditCard) {
            if (!empty($creditCard['type']) && isset($creditCard['type'])) {
                $this->type = $creditCard['type'];
            }
            if (!empty($creditCard['number']) && isset($creditCard['number'])) {
                $this->number = $creditCard['number'];
            }
            if (!empty($creditCard['expire_month']) && isset($creditCard['expire_month'])) {
                $this->expireMonth = $creditCard['expire_month'];
            }
            if (!empty($creditCard['expire_year']) && isset($creditCard['expire_year'])) {
                $this->expireYear = $creditCard['expire_year'];
            }
            if (!empty($creditCard['cvv2']) && isset($creditCard['cvv2'])) {
                $this->cvv2 = $creditCard['cvv2'];
            }
            if (!empty($creditCard['first_name']) && isset($creditCard['first_name'])) {
                $this->firstName = $creditCard['first_name'];
            }
            if (!empty($creditCard['last_name']) && isset($creditCard['last_name'])) {
                $this->lastName = $creditCard['last_name'];
            }
            if (!empty($creditCard['card_token']) && isset($creditCard['card_token'])) {
                $this->cardToken = $creditCard['card_token'];
            }
            if (!empty($creditCard['email']) && isset($creditCard['email'])) {
                $this->email = $creditCard['email'];
            }
        }

        if ($this->type && $this->number && $this->expireMonth && $this->expireYear && ($this->nameOnCard || ($this->firstName && $this->lastName))) {
            if ($this->firstName && $this->lastName && !$this->nameOnCard) {
                $this->nameOnCard = $this->firstName . ' ' . $this->lastName;
            }
            else if ($this->nameOnCard && (!$this->firstName || !$this->lastName)) {
                $parts = explode(' ', $this->nameOnCard);
                $this->firstName = $parts[0];
                if (sizeof($parts) > 1) {
                    $this->lastName = $parts[1];
                }
            }
            $creditCardValidator = new CreditCardValidator();
            $this->valid = $creditCardValidator->validate($this->number, $this->type, $this->errornumber, $this->errortext);
        }

    }

    public function setBillingCountry($countryCode) { $this->billingCountry = $countryCode; }
    public function getType(){return $this->type?strtolower($this->type):$this->type;}
    public function getNumber(){return $this->number;}
    public function getExpireMonth(){return $this->expireMonth;}
    public function getExpireYear(){return $this->expireYear;}
    public function getCvv2(){return $this->cvv2;}
    public function getNameOnCard(){return $this->nameOnCard;}
    public function getFirstName(){return $this->firstName;}
    public function getLastName(){return $this->lastName;}
    public function getCardToken(){return $this->cardToken;}
    public function getEmail(){return $this->email;}
    public function getBillingCountry() { return $this->billingCountry?strtoupper($this->billingCountry):$this->billingCountry; }
    public function isValid() { return $this->valid; }
    public function getErrorNumber() { return $this->errornumber; }
    public function getErrorText() { return $this->errortext; }
    public function setCreditCardId($creditCardId) { $this->creditCardId = $creditCardId; }
    public function getCreditCardId() { return $this->creditCardId; }
}