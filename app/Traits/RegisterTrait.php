<?php

namespace App\Traits;

use App\Models\User;
use App\Models\SocialUser;
use App\Utils;
use App\Consts;
use DB;
use Exception;
use Mail;
use App\Mails\VerificationMailQueue;
use App\Jobs\PushAcountInfoHubSpotJob;
use App\Jobs\CreateMattermostUserEndpoint;
use App\Jobs\CreateNodebbUserEndpoint;
use Illuminate\Auth\Events\Registered;
use Carbon\Carbon;
use App\Http\Services\MattermostService;
use App\Exceptions\Reports\MattermostException;
use App\PhoneUtils;
use Aws;

trait RegisterTrait {

    public function doRegister($input, $options = [])
    {
        $user = null;
        DB::beginTransaction();
        try {
            $user = $this->createUser($input, $options);

            DB::commit();

            event(new Registered($user));
            PushAcountInfoHubSpotJob::dispatch($user);

            $data = [
                'when' => 'signup',
                'data' => [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]
            ];
            Aws::performFirehosePut($data, $user);

            CreateNodebbUserEndpoint::dispatch($user)->onQueue(Consts::CREATE_NODEBB_USER_ENDPOINT_QUEUE);

            CreateMattermostUserEndpoint::dispatchNow($user);

            return $user;
        } catch (Exception $ex) {
            DB::rollback();
            logger()->error($ex);

            if ($ex instanceof MattermostException) {
                $cloneUser = clone $user;
                $username = sprintf('%s_%s', $cloneUser->username, Utils::currentMilliseconds());
                $cloneUser->username = $username;
                logger('==========Register mattermost user with currentMilliseconds============', [$cloneUser]);

                CreateMattermostUserEndpoint::dispatch($cloneUser)->onQueue(Consts::CREATE_MATTERMOST_USER_ENDPOINT_QUEUE);

                return $user;
            }

            throw $ex;
        }
    }

    private function createUser($data, $options = [])
    {
        $user = User::firstOrNew([
            'email' => strtolower($data['email'])
        ]);

        $password = bcrypt(array_get($data, 'password', Utils::generateRandomString(Consts::PASSWORD_SOCIAL_LENGTH)));
        $user->password = $password;
        $user->dob = $data['dob'];
        $user->username = $data['username'];
        $user->sex = $data['sex'];
        $user->status = Consts::USER_ACTIVE;

        $user->languages = [Consts::DEFAULT_LOCALE];

        $user->phone_country_code = $data['phone_country_code'];
        $user->phone_number = PhoneUtils::makePhoneNumber($data['phone_number'], $data['phone_country_code']);

        if (isset($data['languages'])) {
            $user->languages = $data['languages'];
        }
        $user->save();

        DB::table('user_settings')->insert(['id' => $user->id]);
        DB::table('user_statistics')->insert(['user_id' => $user->id]);

        // create social_user if login with social network
        if (!empty($options['provider'])) {
            $this->createSocialUser($user, $options['provider'], $options['provider_user']);
        }

        return $user;
    }

    private function createSocialUser($user, $provider, $providerUser)
    {
        $socialUser = new SocialUser;
        $socialUser->provider = $provider;
        $socialUser->provider_id= $providerUser->id;
        $socialUser->email = strtolower($providerUser->email);

        $user->socialUser()->save($socialUser);
    }
}
