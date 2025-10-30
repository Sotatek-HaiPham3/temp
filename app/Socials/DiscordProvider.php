<?php

namespace App\Socials;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class DiscordProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    // protected $scopeSeparator = ' ';

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [
        'identify',
        'connections',
        'email',
        'guilds'
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://discord.com/api/oauth2/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://discord.com/api/oauth2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken($code)
    {
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)],
            'body'    => $this->getTokenFields($code),
        ]);

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenFields($code)
    {
        return parent::getTokenFields($code) + ['grant_type' => 'authorization_code'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        $userUrl = 'https://discordapp.com/api/v6/users/@me';

        $response = $this->getHttpClient()->get($userUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
        ]);

        $user = json_decode($response->getBody(), true);

        return $user;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'        => $user['id'],
            'nickname'  => $user['username'],
            'locale'    => $user['locale'],
            'email'     => $user['email'],
            'avatar'    => $this->getAvatarUrl($user)
        ]);
    }

    private function getAvatarUrl($user)
    {
        if (empty($user['avatar'])) {
            return null;
        }

        return "https://cdn.discordapp.com/avatars/{$user['id']}/{$user['avatar']}.jpg";
    }
}
