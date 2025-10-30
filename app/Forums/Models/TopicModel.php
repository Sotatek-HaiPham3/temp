<?php

namespace App\Forums\Models;

use Psr\Http\Message\ResponseInterface;

class TopicModel extends AbstractModel
{
    /**
     * @var string
     */
    public static $endpoint = '/topics';

    /**
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function createTopic($requestOptions)
    {
        return $this->client->post(self::$endpoint, $requestOptions);
    }

    /**
     * @param array $tid
     * @return ResponseInterface
     */
    public function deleteTopic($tid)
    {
        return $this->client->delete(self::$endpoint .'/'. $tid);
    }

    /**
     * @param int $tid
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function createComment($tid, $requestOptions)
    {
        return $this->client->post(self::$endpoint . '/' . $tid, $requestOptions);
    }

    /**
     * @param array $username
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function getTopicsForUser($username, $requestOptions)
    {
        return $this->client->get('user/' . $username . '/topics', $requestOptions);
    }

    /**
     * @param array $slug
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function getPostsForTopic($slug, $requestOptions)
    {
        return $this->client->get('topic/' . $slug, $requestOptions);
    }
}
