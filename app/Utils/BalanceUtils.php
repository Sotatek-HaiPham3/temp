<?php

namespace App\Utils;

use App\Http\Services\SystemNotification;

class BalanceUtils {

    public static function standardValue($userId, $balance)
    {
        $shouldReport = false;

        if (BigNumber::new($balance->coin)->isNegative()) {
            $shouldReport = true;
            $balance->coin = 0;
        }

        if (BigNumber::new($balance->bar)->isNegative()) {
            $shouldReport = true;
            $balance->bar = 0;
        }

        if ($shouldReport) {
            static::report($userId, $balance);
        }

        return $balance;
    }

    private static function report($userId, $balance)
    {
        logger()->warning('=======[Warning] Balancer Negative: ', ['balance' => $balance]);
        if (Utils::isProduction()) {
            $jsonBalance = json_encode($balance);
            $content = "The balance user id {$userId} is negative. \n Detail: {$jsonBalance}";
            SystemNotification::sendExceptionEmail($content);
        }
    }
}
