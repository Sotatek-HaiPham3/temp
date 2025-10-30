<?php

namespace App\Utils;

use App\Consts;

class BankName
{
    public static function getCreditCardName($name, $card)
    {
        $text       = substr($card, -4);
        $nameShort  = substr($name, 0, 20);
        return "$nameShort **** $text";
    }

    public static function getPaypalName($name, $email)
    {
        $text       = substr($email, 0, 6);
        $nameShort  = substr($name, 0, 20);
        return "$nameShort $text **** ";
    }
}
