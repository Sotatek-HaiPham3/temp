<?php

namespace App\Http\Services;

use App\Consts;
use App\Exceptions\Reports\MattermostException;
use App\Mattermost\CustomDriver;
use App\Mattermost\CustomModel;
use Gnello\Mattermost\Driver;
use Pimple\Container;
use App\Models\User;
use App\Models\MattermostUser;
use App\Utils;
use App\Utils\BearerToken;
use Auth;
use Exception;

class Mattermost {

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

    public static function getUsersByIds($ids)
    {
        $result = static::authenticateForSystem()
                    ->getUserModel()
                    ->getUsersByIds($ids);

        if (static::isResponseSucceed($result)) {
            return static::getContents($result);
        }

        return null;
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

    public static function getTeamMembersByIds($mattermostUserIds)
    {
        $systemProvider = static::authenticateForSystem();
        $result = $systemProvider->getTeamModel()
            ->getTeamMembersByIds(static::$mattermostTeamId, $mattermostUserIds);

        return static::handleResult($result);
    }

    public static function createUserEndpoint($email, $username)
    {
        $email      = strtolower($email);
        $username   = strtolower($username);

        $systemProvider = static::authenticateForSystem();

        $result = $systemProvider->getUserModel()->createUser([
                'email'    => $email,
                'username' => $username,
                'password' => config('mattermost.members.user.default_password')
            ]);

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

    public static function addUsersIntoMattermostTeam($mattermostUserIds)
    {
        $systemProvider = static::authenticateForSystem();

        $mattermostUserIds->each(function ($mattermostUserId) use ($systemProvider) {
            static::addUserIntoMattermostTeam($mattermostUserId, $systemProvider);
        });
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
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->createDirectMessageChannel([
                $mattermostUserId,
                $oppositeMattermostUserId
            ]);

        return static::handleResult($result);
    }

    public static function createChannel($params)
    {
        $mattermostUserId = Auth::user()->mattermostUser->mattermost_user_id;
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $params['team_id'] = static::$mattermostTeamId;
        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->createChannel($params);

        return static::handleResult($result);
    }

    public static function createPost($mattermostUserId, $posts)
    {
        $userConfig = [
            'login_id' => array_get($posts, 'login_id'),
            'password' => config('mattermost.members.user.default_password')
        ];

        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
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

    public static function patchPost($postId, $params)
    {
        $configure = static::getSystemConfiguration();
        $result = static::authenticateForUser($configure)
            ->getPostModel()->patchPost($postId, $params);

        return static::handleResult($result);
    }

    public static function deletePost($mattermostUserId, $params, $isOwner = false)
    {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);
        if ($isOwner) {
            $configure = static::getSystemConfiguration();
        }

        $result = static::authenticateForUser($configure)
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

    public static function pinPost($mattermostUserId, $postId)
    {
        $result = static::authenticateForSystem()
            ->getPostModel()
            ->pinPost($postId);

        return static::handleResult($result);
    }

    public static function unpinPost($mattermostUserId, $postId)
    {
        $result = static::authenticateForSystem()
            ->getPostModel()
            ->unpinPost($postId);

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

    public static function getPublicChannels($mattermostUserId)
    {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->getPublicChannels(static::$mattermostTeamId);

        return static::handleResult($result);
    }

    public static function deleteChannel($mattermostUserId, $channelId)
    {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->deleteChannel($channelId);

        return static::handleResult($result);
    }

    public static function getChannelsPinnedPosts($mattermostUserId, $channelId)
    {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForSystem()
            ->getChannelModel()
            ->getChannelsPinnedPosts($channelId);

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

    public static function addUserToChannel($channelId, $mattermostUserId) {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getChannelModel()
            ->addUser($channelId, [
                'user_id' => $mattermostUserId
            ]);

        return static::handleResult($result);
    }

    public static function removeUserFromChannel($channelId, $mattermostUserId) {
        $result = static::authenticateForSystem()
            ->getChannelModel()
            ->removeUserFromChannel($channelId, $mattermostUserId);

        return static::handleResult($result);
    }

    public static function reactionPost($mattermostUserId, $postId, $emojiName) {
        $configure = static::getConfigureByMattermostUser($mattermostUserId);

        $result = static::authenticateForUser($configure)
            ->getReactionModel()
            ->saveReaction([
                'user_id' => $mattermostUserId,
                'post_id' => $postId,
                'emoji_name' => $emojiName
            ]);

        return static::handleResult($result);
    }

    public static function getReactions($postId)
    {
        $result = static::authenticateForUser()->getPostModel()->getReactions($postId);
        return static::handleResult($result);
    }

    public static function deleteReaction($mattermostUserId, $postId, $emojiName)
    {
        $result = static::authenticateForUser()->getCustomModel()->deleteReaction($mattermostUserId, $postId, $emojiName);
        return static::handleResult($result);
    }

    public static function getPost($postId)
    {
        $result = static::authenticateForUser()->getPostModel()->getPost($postId);
        return static::handleResult($result);
    }

    private static function handleResult($result)
    {
        $contents = static::getContents($result);

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
            ->select('users.id', 'users.email', 'mattermost_users.mattermost_email')
            ->first();

        if (! $user) {
            logger()->error('Data wrong', [
                'mattermost_user_id', $mattermostUserId,
                'user_id_logged', Auth::id()
            ]);
            throw new Exception('Some thing wrong with configuration Mattermost');
        }

        return [
            'id' => $user->id,
            'login_id' => $user->mattermost_email,
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

            $user = Auth::user();
            $email = $user ? $user->mattermostUser->mattermost_email : null;

            $configure = array_merge($configure, [
                // 'login_id' => strtolower(Auth::user()->username)
                'login_id' => $email
            ], $userConfig);
            return static::authenticateGrantPassword($configure);
        }
    }

    public static function getTokenUser($email, $isSaveToken = false)
    {
        $configure = array_merge(static::getUserConfiguration(),[
            'login_id' => $email,
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
        if (! $isValid) {
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
        if (! array_key_exists('token', $configure)) {
            throw new Exception('Token invalid.');
        }

        return static::initDriver($configure);
    }

    private static function initDriver($configure)
    {
        $container = new Container([
            'driver' => $configure
        ]);

        $driver = new CustomDriver($container);
        $response = $driver->authenticate();

        logger()->info('=========Mattermost::initDriver ', [
            'configure' => static::maskData($configure),
            'token' => $response->getHeader('Token'),
            'status_code' => $response->getStatusCode(),
            'content' => json_decode($response->getBody()->getContents()),
            'content_detai' => $response->getBody()->getContents()
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
