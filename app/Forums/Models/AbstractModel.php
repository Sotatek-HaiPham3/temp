<?php

namespace App\Forums\Models;

abstract class AbstractModel
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * AbstractModel constructor.
     *
     * @param Client $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }
}
