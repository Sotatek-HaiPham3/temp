<?php

namespace App\Forums\Models;

use Psr\Http\Message\ResponseInterface;

class UserModel extends AbstractModel
{

    /**
     * @var string
     */
    public static $endpoint = '/users';

    /**
     * @param $username
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function getUserInfo($username, array $requestOptions)
    {
        return $this->client->get('user/' . $username, $requestOptions);
    }

    /**
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function createUser(array $requestOptions)
    {
        return $this->client->post(self::$endpoint, $requestOptions);
    }

    /**
     * @param $userId
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function updateUser($userId, array $requestOptions)
    {
        return $this->client->put(self::$endpoint . '/' . $userId, $requestOptions);
    }

    /**
     * @param $userId
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function createToken($userId, array $requestOptions)
    {
        return $this->client->post(self::$endpoint . '/' . $userId . '/tokens', $requestOptions);
    }

    /**
     * @param $userId
     * @param $token
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function revokeToken($userId, $token)
    {
        return $this->client->delete(self::$endpoint . '/' . $userId . '/tokens/' . $token);
    }
}
