<?php

namespace App\OAuth;

interface CustomRequestProviderInterface
{
    /**
     * Get a social user from the username.
     *
     * @param string $userName
     *
     * @return \League\OAuth2\Server\Entities\UserEntityInterface
     */
    public function getUserEntityUsername($userName);
}
