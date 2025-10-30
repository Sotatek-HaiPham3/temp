<?php

namespace App\Forums\Models;

use Psr\Http\Message\ResponseInterface;

class PostModel extends AbstractModel
{
    /**
     * @var string
     */
    public static $endpoint = '/posts';

    /**
     * @param int $tid
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function vote($pid, $requestOptions)
    {
        return $this->client->post(self::$endpoint . '/' . $pid . '/vote', $requestOptions);
    }

    /**
     * @param int $tid
     * @return ResponseInterface
     */
    public function unvote($pid)
    {
        return $this->client->delete(self::$endpoint . '/' . $pid . '/vote');
    }
}
