<?php
/*
 * File name: InvalidPaymentInfoException.php
 * Last modified: 2025.04.24 at 17:07:53
 * Author: harrykouevi - https://github.com/harrykouevi
 * Copyright (c) 2025
 */

namespace App\Exceptions;

use Exception;
use App\Models\User;
use App\Models\Wallet;

class InvalidPaymentInfoException extends Exception
{
    protected $status = 422;

    public function __construct(array $paymentInfo)
    {
        $message = $this->validate($paymentInfo);
        parent::__construct($message, $this->status);
    }

    protected function validate(array $paymentInfo)
    {
        if (!array_key_exists('amount',$paymentInfo) || !array_key_exists("payer_wallet",$paymentInfo) || !array_key_exists('user',$paymentInfo)) {
            return 'Invalid payment information. Missing required fields.';
        }

        if (!is_numeric($paymentInfo['amount'])) {
            return 'The amount must be not null and numeric.';
        }

        if (
            !is_int($paymentInfo['payer_wallet']) &&
            !is_string($paymentInfo['payer_wallet']) &&
            !($paymentInfo['payer_wallet'] instanceof Wallet)
        ) {
            return 'Payer wallet must be an integer, string or Wallet instance.';
        }

        if (!is_null($paymentInfo['user'])) {
            if (!($paymentInfo['user'] instanceof User)) {
                return 'User must be a valid User object.';
            }
        }

       
        // Aucune erreur
        return null;
    }

    // Dans InvalidPaymentInfoException.php
    public static function check(array $paymentInfo): void
    {
        $instance = new self($paymentInfo);
        if ($instance->getMessage()) {
            throw $instance;
        }
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }
}
