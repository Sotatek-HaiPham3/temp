<?php

namespace App\Http\Services;

use App\Exceptions\Reports\MattermostException;
use Gnello\Mattermost\Driver;
use Pimple\Container;
use App\Models\User;
use App\Models\MattermostUser;
use App\Utils;
use App\Utils\BearerToken;
use Auth;
use Exception;

class Mattermost
{

    private static $mattermostTeamId;

    public function __construct($mattermostTeamId)
    {
        static::$mattermostTeamId = $mattermostTeamId;
    }

    public static function closeDriver()
    {
        return static::authenticateForUser()
            ->getUserModel()
            ->logoutOfUserAccount();
    }

    private static function getUser($username)
    {
        return User::where(function ($query) use ($username) {
            $query->where('email', $username)
                ->orWhere('username', $username);
        })
            ->first();
    }

    public static function getUserByEmail($email)
    {
        $result = static::authenticateForSystem()
            ->getUserModel()
            ->getUserByEmail($email);

        if (static::isResponseSucceed($result)) {
            return static::getContents($result);
        }

        return null;
    }

    public static function getUserByUsername($username)
    {
        $result = static::authenticateForSystem()
            ->getUserModel()
            ->getUserByUsername($username);

        if (static::isResponseSucceed($result)) {
            return static::getContents($result);
        }

        return null;
    }

    public static function createMattermostTeam()
    {
        $systemProvider = static::authenticateForSystem();

        $teamName = config('mattermost.team.name');
        $teamType = config('mattermost.team.type');

        $result = $systemProvider->getTeamModel()
            ->getTeamByName($teamName);

        if (static::isResponseSucceed($result)) {
            $contents = static::getContents($result);
            return $contents->id;
        }

        $result = $systemProvider->getTeamModel()
            ->createTeam([
                'name'          => $teamName,
                'display_name'  => $teamName,
                'type'          => $teamType
            ]);

        $result = static::getContents($result);

        return $result->id;
    }

    public static function createUserEndpoint($email, $username)
    {
        $email      = strtolower($email);
        $username   = strtolower($username);

        $systemProvider = static::authenticateForSystem();

        $result = $systemProvider->getUserModel()->createUser([
            'email'    => "m_{$email}",
            'username' => "m_{$username}",
            'password' => config('mattermost.members.user.default_password')
        ]);
logger("======result mattermost======", [$email, $username, $result]);
        $result = static::handleResult($result);

        static::addUserIntoMattermostTeam($result->id, $systemProvider);

        return $result;
    }

    public static function updateEmailUser($mattermostUserId, $oldEmail, $newEmail)
    {
        $provider = static::authenticateForUser([
            'login_id' => $oldEmail
        ]);

        $resUser = $provider->getUserModel()->getUser($mattermostUserId);
        $user = static::handleResult($resUser);

        $result = $provider
            ->getUserModel()
            ->updateUser($mattermostUserId, array_merge((array) $user, [
                'id'    => $mattermostUserId,
                'email' => $newEmail,
                'password' => config('mattermost.members.user.default_password')
            ]));

        return static::handleResult($result);
    }

    private static function addUserIntoMattermostTeam($mattermostUserId, $systemProvider)
    {
        if (!static::$mattermostTeamId) {
            throw new MattermostException('mattermost.mattermost_team_id.not_exists');
        }

        $result = $systemProvider->getTeamModel()->addUser(static::$mattermostTeamId, [
            'team_id' => static::$mattermostTeamId,
            'user_id' => $mattermostUserId
        ]);

        return static::handleResult($result);
    }

    public static function createDirectMessageChannel($mattermostUserId, $oppositeMattermostUserId)
    {
        $result = static::authenticateForUser()
            ->getChannelModel()
            ->createDirectMessageChannel([
                $mattermostUserId,
                $oppositeMattermostUserId
            ]);

        return static::handleResult($result);
    }

    public static function createPost($posts)
    {
        $userConfig = [
            'login_id' => array_get($posts, 'login_id'),
            'password' => config('mattermost.members.user.default_password')
        ];

        $result = static::authenticateForUser($userConfig)
            ->getPostModel()
            ->createPost($posts);

        return static::handleResult($result);
    }

    public static function createPostSystem($posts)
    {
        $result = Mattermost::authenticateForSystem()
            ->getPostModel()
            ->createPost($posts);

        return static::handleResult($result);
    }

    public static function updatePost($postId, $params)
    {
        $result = static::$driver->getPostModel()->updatePost($postId, $params);
        return static::handleResult($result);
    }

    public static function deletePost($params)
    {
        $result = static::authenticateForUser()
            ->getPostModel()
            ->deletePost($params);

        return static::handleResult($result);
    }

    public static function getPostsForChannel($channelId, $input)
    {
        $result = static::authenticateForSystem()
            ->getPostModel()
            ->getPostsForChannel($channelId, $input);

        return static::handleResult($result);
    }

    public static function getChannelsForUser($mattermostUserId)
    {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->getChannelsForUser($mattermostUserId, static::$mattermostTeamId);

        return static::handleResult($result);
    }

    public static function getUnreadMessages($mattermostUserId, $channelId)
    {
        $result = static::authenticateForSystem()
            ->getChannelModel()
            ->getUnreadMessages($mattermostUserId, $channelId);

        return static::handleResult($result);
    }

    public static function viewChannel($mattermostUserId, $channelId)
    {
        $result = static::authenticateForUser()
            ->getChannelModel()
            ->viewChannel($mattermostUserId, [
                'channel_id' => $channelId
            ]);

        return static::handleResult($result);
    }

    public static function getChannelById($channelId)
    {
        $result = static::authenticateForSystem()
            ->getChannelModel()
            ->getChannel($channelId);

        return static::handleResult($result);
    }

    private static function handleResult($result)
    {
        $contents = static::getContents($result);
logger("===responde content====", [$contents]);
        if (!$contents) {
            throw new MattermostException('mattermost.network_error');
        }

        if (static::isResponseSucceed($result)) {
            return $contents;
        }

        throw new MattermostException($contents->id, $contents->message);
    }

    private static function isResponseSucceed($result)
    {
        return in_array($result->getStatusCode(), [200, 201]);
    }

    private static function getContents($result)
    {
        return json_decode($result->getBody()->getContents());
    }

    private static function getConfigureByMattermostUser($mattermostUserId)
    {
        $user = MattermostUser::join('users', 'mattermost_users.user_id', 'users.id')
            ->where('mattermost_users.mattermost_user_id', $mattermostUserId)
            ->select('users.id', 'users.email')
            ->first();

        if (!$user) {
            logger()->error('Data wrong', [
                'mattermost_user_id', $mattermostUserId,
                'user_id_logged', Auth::id()
            ]);
            throw new Exception('Some thing wrong with configuration Mattermost');
        }

        // myself is logged in
        if ($user->id === Auth::id()) {
            return [];
        }

        return [
            'id' => $user->id,
            'login_id' => "m_{$user->email}",
            'password' => config('mattermost.members.user.default_password')
        ];
    }

    private static function authenticateForSystem()
    {
        $configure = static::getSystemConfiguration();

        /* 
         * if has token , will authenticate by token
            static::static::authenticateByToken($configure, $token);

            $token = BearerToken::fromRequest();
            if ($token) {
                return static::authenticateByToken(array_merge($configure, ['token' => $token ]);
            }
         */

        return static::authenticateGrantPassword($configure);
    }

    /*
     * Expected user authenticated.
     */
    private static function authenticateForUser($userConfig = [])
    {
        $configure = static::getUserConfiguration();

        $shouldPriority = !empty($userConfig['login_id']) && !empty($userConfig['password']);
        if ($shouldPriority) {
            return static::authenticateGrantPassword(array_merge($configure, $userConfig));
        }

        try {
            $token = BearerToken::fromRequest();
            if (!$token) {
                throw new Exception('Token fromRequest is invalid');
            }

            return static::authenticateByToken(array_merge($configure, [
                'token' => $token->mattermost_token
            ]));
        } catch (Exception $ex) {
            logger('=======authenticateForUser::exception:: ', [$ex]);

            $user = Auth::user() ?? ['email' => null];

            $configure = array_merge($configure, [
                // 'login_id' => strtolower(Auth::user()->username)
                'login_id' => "m_{$user['email']}"
            ], $userConfig);
            return static::authenticateGrantPassword($configure);
        }
    }

    public static function getTokenUser($email, $isSaveToken = false)
    {
        $configure = array_merge(static::getUserConfiguration(), [
            'login_id' => "m_{$email}",
            'password' => config('mattermost.members.user.default_password')
        ]);

        $token = static::authenticateGrantPassword($configure, true);

        if ($isSaveToken) {
            logger()->info('==============Save mattermost token for user: ', [
                'configure' => static::maskData($configure)
            ]);
            static::saveUserToken($configure['login_id'], $token);
        }

        return $token;
    }

    private static function authenticateGrantPassword($configure, $fetchToken = false)
    {
        $isValid = array_key_exists('login_id', $configure) && array_key_exists('password', $configure);
        if (!$isValid) {
            throw new Exception('Cannot authenticate with Mattermost.');
        }

        $configure['login_id'] = strtolower($configure['login_id']);

        list($accessToken, $driver) = static::initDriver($configure);

        if ($fetchToken) {
            return $accessToken;
        }

        $isSystemAccount = !empty($configure['is_system_account']);
        if ($isSystemAccount) {
            static::saveSystemToken($accessToken);
        }

        $isUserAccount = !empty($configure['is_user_account']);
        if ($isUserAccount) {
            logger()->info('==============Save mattermost token for user: ', [
                'configure' => static::maskData($configure)
            ]);
            static::saveUserToken($configure['login_id'], $accessToken);
        }

        return $driver;
    }

    private static function saveSystemToken($accessToken)
    {
        // TODO: save token system account.
    }

    private static function saveUserToken($loginId, $accessToken)
    {
        try {
            $token = BearerToken::fromRequest();

            $shouldDo = $token && $token->user_id === Auth::id()
                && strtolower($loginId) === strtolower(Auth::user()->email);

            if ($shouldDo) {
                $token->mattermost_token = $accessToken;
                $token->save();
            }
        } catch (Exception $ex) {
            // Do something
            logger()->error('=====saveUserToken:: ', ['exception' => $ex]);
        }
    }

    private static function authenticateByToken($configure)
    {
        if (!array_key_exists('token', $configure)) {
            throw new Exception('Token invalid.');
        }

        return static::initDriver($configure);
    }

    private static function initDriver($configure)
    {
        $container = new Container([
            'driver' => $configure
        ]);

        $driver = new Driver($container);
        $response = $driver->authenticate();

        logger()->info('=========Mattermost::initDriver ', [
            'configure' => static::maskData($configure),
            'token' => $response->getHeader('Token'),
            'status_code' => $response->getStatusCode(),
            'content' => json_decode($response->getBody()->getContents())
        ]);

        if (!static::isResponseSucceed($response)) {
            throw new Exception('Some errors with Mattermost.');
        }

        if (array_key_exists('token', $configure)) {
            return $driver;
        }

        $token = $response->getHeader('Token')[0];

        logger()->info('=======Configuration: ', ['configure' => static::maskData($configure), 'token' => $token]);

        return [$token, $driver];
    }

    private static function maskData($data)
    {
        $attributes = ['password'];
        return Utils::maskData($data, $attributes);
    }

    private static function getSystemConfiguration()
    {
        $configHost = static::getHostConfiguration();

        $systemConfig = [
            'login_id' => config('mattermost.members.system.email'),
            'password' => config('mattermost.members.system.password'),
            'is_system_account' => true
        ];

        return array_merge($configHost, $systemConfig);
    }

    private static function getUserConfiguration($userConfig = [])
    {
        $configHost = static::getHostConfiguration();

        $defaultConfig = [
            'password' => config('mattermost.members.user.default_password'),
            'is_user_account' => true
        ];

        return array_merge($configHost, $defaultConfig, $userConfig);
    }

    private static function getHostConfiguration()
    {
        $endpoint = config('mattermost.url');

        if (!$endpoint) {
            throw new Exception('Mattermost missing configuration');
        }

        $host   = parse_url($endpoint, PHP_URL_HOST);
        $port   = parse_url($endpoint, PHP_URL_PORT);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($port) {
            $host = "{$host}:{$port}";
        }

        return [
            'url'       => $host,
            'scheme'    => $scheme
        ];
    }
}
