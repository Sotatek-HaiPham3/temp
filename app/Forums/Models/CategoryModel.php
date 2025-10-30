<?php

namespace App\Forums\Models;

use Psr\Http\Message\ResponseInterface;

class CategoryModel extends AbstractModel
{

    /**
     * @var string
     */
    public static $endpoint = '/categories';

    /**
     * @param array $requestOptions
     * @return ResponseInterface
     */
    public function create($requestOptions)
    {
        return $this->client->post(self::$endpoint, $requestOptions);
    }

    /**
     * @param array $slug
     * @return ResponseInterface
     */
    public function delete($slug)
    {
        return $this->client->delete(self::$endpoint . '/' . $slug);
    }
}
