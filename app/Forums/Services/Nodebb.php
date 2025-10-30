<?php

namespace App\Forums\Services;

use App\Exceptions\Reports\NodebbException;
use App\Forums\Bases\Driver;
use Pimple\Container;
use App\Models\Setting;
use App\Models\NodebbUser;
use App\Utils;
use App\Consts;
use App\Utils\BearerToken;
use Auth;
use Exception;
use GuzzleHttp\Cookie\CookieJar;
use App\Models\User;

class Nodebb {

    private static $apiPrefix = '/api/v2';

    private static $cookieJar;
    private static $categoryPostId;
    private static $categoryVideoId;

    public function __construct($categoryPostId, $categoryVideoId)
    {
        static::$categoryPostId = $categoryPostId;
        static::$categoryVideoId = $categoryVideoId;
        static::$cookieJar = new CookieJar();
    }

    public static function closeDriver()
    {
        $token = BearerToken::fromRequest();

        if (!$token) {
            return;
        }

        return static::authenticateForUser()
            ->setApiPrefix(static::$apiPrefix)
            ->getUserModel()
            ->revokeToken(Auth::user()->nodebbUser->nodebb_user_id, $token->nodebb_token);
    }

    public static function createCategory($key, $categoryName)
    {
        if (static::checkExistsCategory($key)) {
            return;
        }

        $systemProvider = static::authenticateForSystem();

        $result = $systemProvider->setApiPrefix(static::$apiPrefix)->getCategoryModel()
            ->create([
                '_uid' => config('nodebb.members.system.uid'),
                'name' => $categoryName
            ]);

        $result = static::handleResult($result);

        return $result->payload->cid;
    }

    private static function checkExistsCategory($key)
    {
        return Setting::getValue($key);
    }

    public static function createUserEndpoint($email, $username)
    {
        $email      = strtolower($email);
        $username   = strtolower($username);

        $systemProvider = static::authenticateForSystem();

        $result = $systemProvider->setApiPrefix(static::$apiPrefix)
            ->getUserModel()->createUser([
                '_uid'     => config('nodebb.members.system.uid'),
                'email'    => $email,
                'username' => $username,
                'password' => config('nodebb.members.user.default_password')
            ]);

        $result = static::handleResult($result);

        return $result;
    }

    public static function getUserInfo($uid, $username)
    {
        $username = strtolower($username);

        $provider = static::authenticateForSystem();
        $result = $provider->setApiPrefix()
            ->getUserModel()->getUserInfo($username, [
                '_uid' => $uid
            ]);

        $contents = static::getContents($result);
        if (static::isResponseSucceed($result)) {
            return $contents;
        }

        return null;
    }

    public static function createComment($tid, $data)
    {
        $content = static::buildContent($data);

        $params = array_merge($data, [
            'content' => $content
        ]);

        $provider = static::authenticateForUser();
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getTopicModel()->createComment($tid, $params);

        return static::handleResult($result);
    }

    public static function createTopic($data)
    {
        $content = static::buildContent($data);

        $params = [
            'title' => 'default',
            'content' => $content,
            'cid' => static::$categoryPostId
        ];

        $provider = static::authenticateForUser();
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getTopicModel()->createTopic($params);

        return static::handleResult($result);
    }

    public static function createTopicForVideo($data, $username)
    {
        $content = static::buildContent($data);

        $params = [
            'title' => 'videos',
            'content' => $content,
            'cid' => static::$categoryVideoId
        ];

        $userConfig = static::getUserConfigForUser($username);
        $provider = static::authenticateForUser($userConfig);
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getTopicModel()->createTopic($params);

        return static::handleResult($result);
    }

    private static function buildContent($data)
    {
        $content = array_get($data, 'content');
        $imagePath = array_get($data, 'imagePath');

        if ($imagePath) {
            $image = static::buildImage($imagePath);
            $content = sprintf('%s%s', $content, $image);
        }

        return $content;
    }

    private static function buildImage($imagePath)
    {
        return sprintf('![](%s)', $imagePath);
    }

    public static function deleteTopic($tid)
    {
        $provider = static::authenticateForUser();
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getTopicModel()->deleteTopic($tid);

        return static::handleResult($result);
    }

    public static function vote($pid, $data)
    {
        $provider = static::authenticateForUser();
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getPostModel()->vote($pid, $data);

        return static::handleResult($result);
    }

    public static function unvote($pid)
    {
        $provider = static::authenticateForUser();
        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getPostModel()->unvote($pid);

        return static::handleResult($result);
    }

    public static function getTopicsForUser($username, $params = [])
    {
        $userConfig = static::getUserConfigForUser($username);
        $provider = static::authenticateForUser($userConfig);
        $result = $provider->setApiPrefix()->getTopicModel()->getTopicsForUser(strtolower($username), $params);

        return static::handleResult($result);
    }

    public static function getPostsForTopic($username, $slug, $params = [])
    {
        $userConfig = static::getUserConfigForUser($username);
        $provider = static::authenticateForUser($userConfig);
        $result = $provider->setApiPrefix()->getTopicModel()->getPostsForTopic($slug, $params);

        $result = static::handleResult($result);

        return $result;
    }

    private static function getUserConfigForUser($username)
    {
        $user = User::select('id')->where('username', $username)->first();

        return [
            '_uid' => $user->nodebbUser->nodebb_user_id,
            'password' => config('nodebb.members.user.default_password')
        ];
    }

    public static function updateEmailBySystem($nodebbUserId, $email)
    {
        $provider = static::authenticateForSystem();

        return static::updateEmail($nodebbUserId, $email, $provider);
    }

    public static function updateEmail($nodebbUserId, $email, $provider = null)
    {
        if (empty($provider)) {
            $provider = static::authenticateForUser();
        }

        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getUserModel()
            ->updateUser($nodebbUserId, [
                '_uid' => $nodebbUserId,
                'email' => strtolower($email),
            ]);

        return static::handleResult($result);
    }

    public static function updateUsernameBySystem($nodebbUserId, $username)
    {
        $provider = static::authenticateForSystem();

        return static::updateUsername($nodebbUserId, $username, $provider);
    }

    public static function updateUsername($nodebbUserId, $username, $provider = null)
    {
        if (empty($provider)) {
            $provider = static::authenticateForUser();
        }

        $result = $provider->setApiPrefix(static::$apiPrefix)
            ->getUserModel()
            ->updateUser($nodebbUserId, [
                '_uid' => $nodebbUserId,
                'username' => strtolower($username),
            ]);

        return static::handleResult($result);
    }

    private static function handleResult($result)
    {
        $contents = static::getContents($result);

        if (static::isResponseSucceed($result)) {
            return $contents;
        }

        $trace = debug_backtrace()[2];
        logger()->info('=========Nodebb::handleResult ', [
            'status_code' => $result->getStatusCode(),
            'content' => $contents,
            'trace' => $trace
        ]);

        throw new NodebbException('nodebb.network_error');
    }

    private static function isResponseSucceed($result)
    {
        return in_array($result->getStatusCode(), [200, 201]);
    }

    private static function getContents($result)
    {
        $result = json_decode($result->getBody()->getContents());

        // $cookies = static::$cookieJar->getCookieByName('express.sid');
        // if ($result && $cookies) {
        //     $result->cookies = $cookies;
        // }

        return $result;
    }

    private static function authenticateForSystem()
    {
        $configure = static::getSystemConfiguration();

        return static::authenticateByToken($configure);
    }

    /*
     * Expected user authenticated.
     */
    private static function authenticateForUser($userConfig = [])
    {
        $configure = static::getUserConfiguration();

        $shouldPriority = !empty($userConfig['_uid']) && !empty($userConfig['password']);
        if ($shouldPriority) {
            return static::authenticateGrantPassword(array_merge($configure, $userConfig));
        }

        try {
            $token = BearerToken::fromRequest();
            if (!$token) {
                throw new Exception('Token fromRequest is invalid');
            }

            return static::authenticateByToken(array_merge($configure, [
                'token' => $token->nodebb_token
            ]));
        } catch (Exception $ex) {
            $uid = Auth::check() ? Auth::user()->nodebbUser->nodebb_user_id : null;

            $configure = array_merge($configure, [
                '_uid' => $uid,
                'password' => config('nodebb.members.user.default_password')
            ], $userConfig);
            return static::authenticateGrantPassword($configure);
        }
    }

    public static function getTokenUser($user, $isSaveToken = false)
    {
        $configure = array_merge(static::getUserConfiguration(),[
            '_uid' => $user->nodebbUser->nodebb_user_id,
            'password' => config('nodebb.members.user.default_password')
        ]);

        $token = static::authenticateGrantPassword($configure, true);

        if ($isSaveToken) {
            logger()->info('==============Save nodebb token for user: ', [
                'configure' => static::maskData($configure)
            ]);
            static::saveUserToken($configure['_uid'], $token);
        }

        return $token;
    }

    private static function authenticateGrantPassword($configure, $fetchToken = false)
    {
        $isValid = array_key_exists('_uid', $configure) && array_key_exists('password', $configure);
        if (! $isValid) {
            throw new Exception('Cannot authenticate with Nodebb.');
        }

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
            logger()->info('==============Save nodebb token for user: ', [
                'configure' => static::maskData($configure)
            ]);
            static::saveUserToken($configure['_uid'], $accessToken);
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
                && $loginId === Auth::user()->nodebbUser->nodebb_user_id;

            if ($shouldDo) {
                $token->nodebb_token = $accessToken->payload->token;
                $token->save();
            }
        } catch (Exception $ex) {
            // Do something
            logger()->error('=====saveUserToken:: ', ['exception' => $ex]);
        }
    }

    private static function authenticateByToken($configure)
    {
        if (! array_key_exists('token', $configure) || empty($configure['token'])) {
            throw new Exception('Token invalid.');
        }

        return static::initDriver($configure);
    }

    private static function initDriver($configure)
    {
        $container = new Container([
            'driver' => array_merge($configure, [
                'cookies' => static::$cookieJar
            ])
        ]);

        $driver = new Driver($container);

        if (array_key_exists('token', $configure)) {
            $driver->setToken($configure['token']);
            return $driver;
        }

        $response = $driver->authenticate();
        $content = static::getContents($response);

        logger()->info('=========Nodebb::initDriver ', [
            'configure' => static::maskData($configure),
            'status_code' => $response->getStatusCode(),
            'content' => $content
        ]);

        if (!static::isResponseSucceed($response)) {
            throw new Exception('Some errors with Nodebb.');
        }

        logger()->info('=======Configuration: ', ['configure' => static::maskData($configure), 'token' => $content->payload->token]);

        return [$content, $driver];
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
            '_uid' => config('nodebb.members.system.uid'),
            'token' => config('nodebb.members.system.token'),
            'is_system_account' => true
        ];

        return array_merge($configHost, $systemConfig);
    }

    private static function getUserConfiguration($userConfig = [])
    {
        $configHost = static::getHostConfiguration();

        $defaultConfig = [
            'password' => config('nodebb.members.user.default_password'),
            'is_user_account' => true
        ];

        return array_merge($configHost, $defaultConfig, $userConfig);
    }

    private static function getHostConfiguration()
    {
        $endpoint = config('nodebb.url');

        if (!$endpoint) {
            throw new Exception('Nodebb missing configuration');
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
