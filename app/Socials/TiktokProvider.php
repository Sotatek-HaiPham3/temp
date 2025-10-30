<?php

namespace App\Socials;

use App\Exceptions\Reports\InvalidCredentialException;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class TiktokProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'TIKTOK';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'user.info.basic',
    ];

    /**
     * {@inheritdoc}
     */
    protected $scopeSeparator = ' ';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://open-api.tiktok.com/platform/oauth/connect/', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://open-api.tiktok.com/oauth/access_token/';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code, $openId)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            'form_params'    => $this->getTokenFields($code),
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByTokenAndOpenId($token, $openId)
    {
        $response = $this->getHttpClient()->get('https://open-api.tiktok.com/oauth/userinfo/', [
            'query' => [
                'open_id' => $openId,
                'access_token' => $token,
            ]
        ]);
        return json_decode($response->getBody(), true);
    }

    /**
     * Get a Social User instance from a known access token.
     *
     * @param  string  $token
     * @return \Laravel\Socialite\Two\User
     */
    public function userFromToken($token)
    {
        //
    }

    /**
     * Get a Social User instance from a known access token.
     *
     * @param  string  $token
     * @return \Laravel\Socialite\Two\User
     */
    public function userFromTokenAndOpenId($token, $openId)
    {
        if (!$openId) {
            // call tiktok get openID
            $resTiktok = $this->getOpenIdTiktok($token);
            $token = $resTiktok['access_token'];
            $openId = $resTiktok['open_id'];
        }
        $user = $this->mapUserToObject($this->getUserByTokenAndOpenId($token, $openId));
        if (!empty($user->user['data']['error_code'])) throw new InvalidCredentialException("invalid_". self::IDENTIFIER ."_token");;
        return $user->setToken($token);
    }

    public function getOpenIdTiktok($code)
    {
        $params = [
            'code' => $code,
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code'
        ];

        $response = $this->getHttpClient()->post('https://open-api.tiktok.com/oauth/access_token/', [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $params
        ]);
        $res = json_decode($response->getBody(), true);
        if (!empty($res['data']['error_code'])) throw new InvalidCredentialException("invalid_". self::IDENTIFIER ."_token");;

        return $res['data'];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id' => Arr::get($user, 'data.union_id'),
            'name' => Arr::get($user, 'data.display_name'),
            'avatar' => Arr::get($user, 'data.avatar_larger'),
            'open_id' => Arr::get($user, 'data.open_id'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return [
            'client_key' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
    }
}
