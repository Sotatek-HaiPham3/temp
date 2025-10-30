<?php

namespace App\Socials;

use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class SnapchatProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Unique Provider Identifier.
     */
    public const IDENTIFIER = 'SNAPCHAT';

    /**
     * {@inheritdoc}
     */
    protected $scopes = [
        'https://auth.snapchat.com/oauth2/api/user.display_name',
        'https://auth.snapchat.com/oauth2/api/user.bitmoji.avatar',
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
        return $this->buildAuthUrlFromBase('https://accounts.snapchat.com/accounts/oauth2/auth', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://accounts.snapchat.com/accounts/oauth2/token';
    }

    public function userFromToken($token)
    {
        try {
            $token = $this->getAccessTokenByCode($token);
        } catch(\Exception $e) {
            logger()->error('==========getAccessTokenByCode==========: ', [$e]);
        }

        $user = $this->getUserByToken($token);

        return $user->setToken($token);
    }

    protected function getAccessTokenByCode($code)
    {
        $this->redirectUrl(env('WEB_APP_URL', 'http://localhost') . '/social-checking');

        $res = $this->getAccessTokenResponse($code);

        return $res['access_token'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://kit.snapchat.com/v1/me?', [
            'query' => [
                'query' => '{me{externalId displayName bitmoji{avatar id}}}',
            ],
            'headers' => [
                'Authorization' => 'Bearer '.$token,
            ],
        ]);

        return $this->mapUserToObject(json_decode($response->getBody(), true));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User())->setRaw($user)->map([
            'id'       => Arr::get($user, 'data.me.externalId'),
            'name'     => Arr::get($user, 'data.me.displayName'),
            'avatar'   => Arr::get($user, 'data.me.bitmoji.avatar'),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
