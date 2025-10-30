<?php
namespace App\Mattermost;


use Gnello\Mattermost\Client;
use Gnello\Mattermost\Models\AbstractModel;
use Gnello\Mattermost\Models\PostModel;
use Gnello\Mattermost\Models\UserModel;
use Psr\Http\Message\ResponseInterface;

class CustomModel extends AbstractModel
{
    /**
     * PreferenceModel constructor.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        parent::__construct($client);
    }

    /**
     * @param $userId
     * @param $postId
     * @param $emojiName
     * @return ResponseInterface
     */
    public function deleteReaction($userId, $postId, $emojiName)
    {
        return $this->client->delete(UserModel::$endpoint . '/' . $userId . PostModel::$endpoint . '/' . $postId . '/reactions/' . $emojiName);
    }
}
