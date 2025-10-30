<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait UserSessionTrait {

    protected function getCurrentUser()
    {
        $user = $this->getUserGuard()->user();

        return [
            'id'                 => $user->id,
            'email'              => $user->email,
            'avatar'             => $user->avatar,
            'sex'                => $user->sex,
            'username'           => $user->username,
            'user_type'          => $user->user_type,
            'ip_address'         => getOriginalClientIp()
        ];
    }

    protected function getUserGuard()
    {
        return Auth::guard('api');
    }
}
