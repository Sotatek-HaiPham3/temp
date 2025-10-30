<?php

namespace App\Traits;

use App;
use App\Exceptions\Reports\AccountDeletedException;
use App\Exceptions\Reports\AccountNotActivedException;
use App\Exceptions\Reports\InvalidCredentialException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use League\OAuth2\Server\Exception\OAuthServerException;

trait GenerateBearerTokenTrait {

    public function generateBearerToken($user)
    {
        $tokenInfo = $this->createToken($user);
        if (isset($tokenInfo['status']) && !$tokenInfo['status']) {
            $errorType = $tokenInfo['error']['key'];
            $msg = trans('auth.failed');
            if ($errorType == 'account_inactive') {
                throw new AccountNotActivedException($msg);
            }

            if ($errorType == 'account_deleted') {
                throw new AccountDeletedException('This account was recently deleted.');
            }
            throw new InvalidCredentialException($msg);
        }
        return [
            'token_type'    => 'Bearer',
            'expires_in'    => $tokenInfo['expires_in'],
            'access_token'  => $tokenInfo['access_token'],
            'refresh_token' => $tokenInfo['refresh_token'],
            'token_expire_time' => $tokenInfo['token_expire_time'],
            'refresh_token_expire_time' => $tokenInfo['refresh_token_expire_time'],
            'locale'        => App::getLocale(),
            'secret'         => null,
        ];
    }

    public function createToken($user)
    {
        if (!empty($user->phone_number)) {
            $userName = $user->phone_number;
        } elseif (!empty($user->username)) {
            $userName = $user->username;
        } else {
            $userName = $user->email;
        }

        request()->request->add([
            'grant_type' => App\Consts::GRAND_TYPE_CUSTOM_REQUEST,
            'client_id' => 1,
            'client_secret' => env('CLIENT_SECRET'),
            'username' => $userName,
            'scope' => '*',
        ]);

        request()->headers->set('Accept', 'application/json');
        request()->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $token = Request::create('api/v1/oauth/token', 'POST');
        $resToken = Route::dispatch($token);

        return json_decode((string) $resToken->getContent(), true);
    }
}
