<?php


namespace App\Mattermost;


use Gnello\Mattermost\Client;
use Gnello\Mattermost\Driver;
use Pimple\Container;

class CustomDriver extends Driver
{
    /**
     * Default options of the Driver
     *
     * @var array
     */
    private $defaultOptions = [
        'scheme' => 'https',
        'basePath' => '/api/v4',
        'url' => 'localhost',
        'login_id' => null,
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
    protected $models = [];

    public function __construct(Container $container)
    {
        $driverOptions = $this->defaultOptions;

        if (isset($container['driver'])) {
            $driverOptions = array_merge($driverOptions, $container['driver']);
        }

        $container['driver'] = $driverOptions;
        $container['client'] = new Client($container);

        $this->container = $container;
        parent::__construct($container);
    }

    /**
     * @return CustomModel
     */

    public function getCustomModel()
    {
        return $this->getModel(CustomModel::class);
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
}
