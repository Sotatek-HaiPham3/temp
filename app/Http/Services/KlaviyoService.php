<?php

namespace App\Http\Services;

use Klaviyo\Klaviyo as Klaviyo;
use Klaviyo\Model\ProfileModel as KlaviyoProfile;
use App\Utils;
use App\Exceptions\Reports\MissingKlaviyoKeyException;
use App\Consts;

class KlaviyoService {

    private $client;

    private $klaviyoApiKey;
    private $klaviyoPublicKey;
    private $klaviyoNewsletterList;

    public function __construct()
    {
        $this->initClient();
    }

    private function initClient()
    {
        $this->klaviyoApiKey = env('KLAVIYO_API_KEY');
        $this->klaviyoPublicKey = env('KLAVIYO_PUBLIC_KEY');
        $this->klaviyoNewsletterList = env('KLAVIYO_NEWSLETTER');

        if (Utils::isProduction()) {
            $this->checkMissingKey();
        }

        $this->client = new Klaviyo($this->klaviyoApiKey, $this->klaviyoPublicKey);
    }

    public function checkMissingKey()
    {
        if (empty($this->klaviyoApiKey) || empty($this->klaviyoPublicKey) || empty($this->klaviyoNewsletterList)) {
            throw new MissingKlaviyoKeyException();
        }
    }

    public function addUser($user)
    {
        $profile = $this->initProfile($user);

        $this->client->publicAPI->identify($profile);
        $this->client->lists->addMembersToList($this->klaviyoNewsletterList, [$profile]);
    }

    public function updateProfile($user)
    {
        $profile = $this->initProfile($user);
        $this->client->lists->subscribeMembersToList($this->klaviyoNewsletterList, [$profile]);
    }

    private function initProfile($user)
    {
        $gamelancer = 'No';
        switch ($user->user_type) {
            case Consts::USER_TYPE_PREMIUM_GAMELANCER:
                $gamelancer = 'Premium Gamelancer';
                break;
            case Consts::USER_TYPE_FREE_GAMELANCER:
                $gamelancer = 'Free Gamelancer';
                break;
            default:
                break;
        }
        $user = [
            '$email' => $user->email,
            '$phone_number' => $user->phone_number,
            'user_id' => $user->id,
            'username' => $user->username,
            'gamelancer' => $gamelancer
        ];

        return new KlaviyoProfile($user);
    }
}
