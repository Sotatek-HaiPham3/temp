<?php

namespace App\OAuth;

use App\Models\User;
use Laravel\Passport\Bridge\User as UserEntity;

class CustomRequestProvider implements CustomRequestProviderInterface
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
    */
    public function getUserEntityUsername($username)
    {
        $user = (new User())->findForPassport($username);

        if (! $user) {
            return false;
        }

        return new UserEntity($user->getAuthIdentifier());
    }
}
