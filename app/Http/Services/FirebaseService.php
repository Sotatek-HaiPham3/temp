<?php

namespace App\Http\Services;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use FCM;
use App\Models\FcmDevice;

class FirebaseService {

    public function registerDevice($userId, $input)
    {
        $device = FcmDevice::firstOrCreate([
            'user_id' => $userId,
            'device_id' => $input['device_id'],
            'token' => $input['token'],
        ]);

        if (! empty($input['device_name'])) {
            $device->device_name = $input['device_name'];
        }

        $device->save();

        return $device;
    }

    public function deleteDevice($userId, $params)
    {
        logger()->info('Delete device token: ', ['user_id' => $userId, 'param' => $params]);

        $devices = FcmDevice::where('user_id', $userId)
            ->where('device_id', $params['device_id'])
            ->delete();

        return $devices;
    }

    public function pushNotification($userId, $params)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60 * 10); // 10 minutes

        $notificationBuilder = new PayloadNotificationBuilder($params['title']);
        $notificationBuilder->setBody($params['body'])
                            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();

        if (!empty($params['data'])) {
            $dataBuilder->addData($params['data']);
        }

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $tokens = $this->getTokenDevices($userId);
        if (!count($tokens)) {
            return;
        }

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        // return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $downstreamResponse->tokensToModify();

        // return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:error) - in production you should remove from your database the tokens
        $downstreamResponse->tokensWithError();
    }

    private function getTokenDevices($userId)
    {
        return FcmDevice::where('user_id', $userId)->pluck('token')->toArray();
    }

}
