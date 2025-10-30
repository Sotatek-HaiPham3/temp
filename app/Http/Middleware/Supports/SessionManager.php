<?php

namespace App\Http\Middleware\Supports;

use Illuminate\Session\SessionManager as BaseSessionManager;

class SessionManager extends BaseSessionManager
{

    /**
     * Create an instance of the database session driver.
     *
     * @return \Illuminate\Session\Store
     */
    protected function createDatabaseDriver()
    {
        $table = $this->config->get('session.table');

        $lifetime = $this->config->get('session.lifetime');

        return $this->buildSession(new DatabaseSessionHandler(
            $this->getDatabaseConnection(), $table, $lifetime, $this->container
        ));
    }

    /**
     * Create the cache based session handler instance.
     *
     * @param  string  $driver
     * @return \Illuminate\Session\CacheBasedSessionHandler
     */
    protected function createCacheHandler($driver)
    {
        $store = $this->config->get('session.store') ?: $driver;

        $cacheDriver = clone $this->container->make('cache')->store($store);
        $cacheDriver->setPrefix($this->config->get('session.prefix'));

        return new CacheBasedSessionHandler(
            $cacheDriver,
            $this->config->get('session.lifetime')
        );
    }
}
