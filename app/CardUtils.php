<?php

namespace App;

use function GuzzleHttp\Psr7\str;
use Illuminate\Validation\ValidationException;
use Inacho\CreditCard;

class CardUtils
{
    public static function validate($cardNumber, $cardExpMonth, $cardExpYear, $cardCvc) {
        $errors = array();

        $card = CreditCard::validCreditCard($cardNumber);
        if (!$card['valid']) {
            $errors = self::addMessage($errors, 'card_number', 'Card Number is invalid');
        }

        $expiryYear = self::getExpiryYear($cardExpYear);
        $isValidExpiryYearMonth = CreditCard::validDate($expiryYear, $cardExpMonth);
        if (!$isValidExpiryYearMonth) {
            $errors = self::addMessage($errors, 'card_exp_month_year', 'Card Date is expired');
        }

        if ($card['valid']) {
            $isValidCVC = CreditCard::validCvc($cardCvc, $card['type']);
            if (!$isValidCVC) {
                $errors = self::addMessage($errors, 'card_cvc', 'Card CVC is invalid');
            }
        }

        if (empty($errors)) {
            return true;
        }

        throw ValidationException::withMessages($errors);
    }

    private static function getExpiryYear($postfixYear)
    {
        // hard core
        return "20{$postfixYear}";
    }

    private static function addMessage($errors, $key, $message)
    {
        $errors[$key] = [$message];
        return $errors;
    }
}