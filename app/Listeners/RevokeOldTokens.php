<?php

namespace App\Listeners;

use App\Consts;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Token;

class RevokeOldTokens {
    public function __construct() {
        //
    }

    public function handle(AccessTokenCreated $event) {
        $userId = $event->userId;
        $infoAccessToken = Token::where('user_id', $userId)->where('revoked', Consts::FALSE);
        if ($infoAccessToken->count() > Consts::USER_ACCESS_TOKEN_LIMIT) {
            $infoAccessToken->orderBy('expires_at', 'asc')->first()->delete();
        }
    }
}
