<?php

namespace App\Exceptions\Reports;

use App\Utils\BigNumber;
use App\Consts;

class NotEnoughBalanceToChatException extends BaseException
{
    public function __construct()
    {
        parent::__construct('exceptions.not_enough_balances_to_chat', $this->message());
    }

    private function message()
    {
        $minCoin = Consts::MIN_COIN_TO_CHAT;
        $params = [
            'coin' => "{$minCoin} coin"
        ];

        if (BigNumber::new($minCoin)->comp(1) > 0) {
            $params = [
                'coin' => "{$minCoin} coins"
            ];
        }
        return __('exceptions.not_enough_balances_to_chat', $params);
    }
}
