<?php

namespace App\Forums\Bases;

use App\Forums\Models\UserModel;
use App\Forums\Models\TopicModel;
use App\Forums\Models\CategoryModel;
use App\Forums\Models\PostModel;
use GuzzleHttp\Psr7\Response;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Driver
 *
 * @package Gnello\Mattermost
 */
class Driver
{
    /**
     * Default options of the Driver
     *
     * @var array
     */
    private $defaultOptions = [
        'scheme' => 'https',
        'url' => 'localhost',
        'password' => null,
        'token' => null,
    ];

    /**
     * @var Container
     */
    private $container;

    /**
     * @var array
     */
    private $models = [];

    /**
     * Driver constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $driverOptions = $this->defaultOptions;

        if (isset($container['driver'])) {
            $driverOptions = array_merge($driverOptions, $container['driver']);
        }

        $container['driver'] = $driverOptions;
        $container['client'] = new Client($container);

        $this->container = $container;
    }

    /**
     * @return ResponseInterface
     */
    public function authenticate()
    {
        $driverOptions = $this->container['driver'];

        if ($driverOptions['token']) {
            $this->setToken($driverOptions['token']);
        }

        $response = $this->setApiPrefix('/api/v2')->getUserModel()->createToken($driverOptions['_uid'], $driverOptions);

        return $response;
    }

    public function setToken($token)
    {
        $this->container['client']->setToken($token);
    }

    public function setApiPrefix($prefix = '/api/')
    {
        $this->container['client']->buildBaseUrl($prefix);
        return $this;
    }

    /**
     * @param $className
     * @return mixed
     */
    private function getModel($className)
    {
        if (!isset($this->models[$className])) {
            $this->models[$className] = new $className($this->container['client']);
        }

        return $this->models[$className];
    }

    /**
     * @return UserModel
     */
    public function getUserModel()
    {
        return $this->getModel(UserModel::class);
    }

    /**
     * @return TopicModel
     */
    public function getTopicModel()
    {
        return $this->getModel(TopicModel::class);
    }

    /**
     * @return CategoryModel
     */
    public function getCategoryModel()
    {
        return $this->getModel(CategoryModel::class);
    }

    /**
     * @return PostModel
     */
    public function getPostModel()
    {
        return $this->getModel(PostModel::class);
    }
}
