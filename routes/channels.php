<?php

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('UserOnline', function ($user) {
    return $user->id;
});

Broadcast::channel('App.VoiceRoom', function ($user) {
    return $user->id;
});

Broadcast::channel('VoiceRoom.{roomId}', function ($user, $roomId) {
    return [
        'sid'     => $user->getSidByRoomId($roomId),
        'user_id' => $user->id,
        'room_id' => $roomId
    ];
});

Broadcast::channel('Community.{communityId}', function ($user, $communityId) {
    return [
        'user_id' => $user->id,
        'community_id' => $communityId
    ];
});

Broadcast::channel('App.Community.{communityId}', function ($user) {
    return $user->id;
});
