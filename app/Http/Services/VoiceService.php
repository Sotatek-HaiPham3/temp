<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use App\Utils\ChatUtils;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\User;
use App\Models\Channel;
use App\Models\VoiceDiary;
use App\Models\UserFollowing;
use App\Models\ChannelMember;
use App\Events\IncomingVoiceCall;
use App\Events\DeclineVoiceCall;
use App\Events\PairedVoiceCall;
use Agora;
use Auth;
use App\Exceptions\Reports\InvalidVoiceChannelException;
use App\Exceptions\Reports\ChannelBlockedException;
use App\Exceptions\Reports\InvalidVoiceCallingException;
use App\Jobs\CheckVoiceCallOutdated;
use App\Http\Services\ChatService;
use Carbon\Carbon;

class VoiceService extends BaseService {

    public function createChannel($username)
    {
        $oppsiteUser = User::where('username', $username)->first();

        if (!$oppsiteUser) {
            $this->throwUserInvalid($username);
        }

        $this->validate($oppsiteUser);

        $channel = Channel::getChatChannel($oppsiteUser->id);

        $channelBlock = ChannelMember::where('channel_id', $channel->id)
            ->where('is_blocked', Consts::TRUE)
            ->exists();

        if ($channelBlock) {
            throw new ChannelBlockedException();
        }

        $followEachOther = UserFollowing::userHasFollowerByFollowingId(Auth::id(), $oppsiteUser->id)
            && UserFollowing::userHasFollowerByFollowingId($oppsiteUser->id, Auth::id());

        $chatEachOther = ChannelMember::userHasSendedMessgae($channel->id, Auth::id())
            && ChannelMember::userHasSendedMessgae($channel->id, $oppsiteUser->id);

        if (!$followEachOther && !$chatEachOther) {
            $this->throwFollowOrChatInvalid();
        }

        $currentMilis = time();
        $hash = gamelancer_hash("$channel->channel_tag_{$currentMilis}");

        VoiceDiary::create([
            'channel_id'    => $channel->id,
            'hash'          => $hash,
            'caller_id'     => Auth::id(),
            'receiver_id'   => $channel->getOppositeUserId(),
            'status'        => Consts::VOICE_STATUS_CREATED
        ]);

        return $hash;
    }

    private function validate($oppsiteUser)
    {
        if ($this->getVoiceDiaryInCalling(Auth::id())) {
            $message = __('exceptions.voice_channel.calling.current_user_invalid');
            $this->throwVoiceCallingInvalid($message);
        }

        if ($this->getVoiceDiaryInCalling($oppsiteUser->id)) {
            $message = __('exceptions.voice_channel.calling.invalid', ['username' => $oppsiteUser->username]);
            $this->throwVoiceCallingInvalid($oppsiteUser->username);
        }
    }

    private function getVoiceDiaryInCalling($userId)
    {
        return VoiceDiary::where(function ($query) use ($userId) {
                $query->where('caller_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->whereNotIn('status', [
                Consts::VOICE_STATUS_CREATED,
                Consts::VOICE_STATUS_DECLINE,
                Consts::VOICE_STATUS_ENDED_CALL
            ])
            ->first();
    }

    public function joinChannel($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        if (!in_array($voiceDiary->status, [Consts::VOICE_STATUS_CREATED, Consts::VOICE_STATUS_CALLING])) {
            $this->throwVoiceChannelInvalid();
        }

        $voiceDiary->status = Consts::VOICE_STATUS_CALLING;
        $voiceDiary->save();

        list($caller, $receiver) = $this->fireEventIncomingVoiceCall($voiceDiary);

        CheckVoiceCallOutdated::addVoice($voiceDiary);

        $agoraUid = Auth::id();
        $token = Agora::generateRtcToken($voiceDiary->hash, $agoraUid);

        return [
            'token'     => $token,
            'user'      => Auth::id() === $caller->id ? $receiver : $caller,
            'caller'    => $caller
        ];
    }

    public function fireEventIncomingVoiceCallIfNeed($userId)
    {
        $voiceDiary = VoiceDiary::where('receiver_id', $userId)
            ->where('status', Consts::VOICE_STATUS_CALLING)
            ->first();

        if (!$voiceDiary) {
            return;
        }

        $this->fireEventIncomingVoiceCall($voiceDiary);
    }

    private function fireEventIncomingVoiceCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);
        $receiver = $this->getUserInfo($voiceDiary->receiver_id);

        $data = [
            'channel_id'    => $voiceDiary->hash,
            'caller'        => $caller,
            'receiver'      => $receiver
        ];

        event(new IncomingVoiceCall($receiver->id, $data));

        return [$caller, $receiver];
    }

    public function declineCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->status = Consts::VOICE_STATUS_DECLINE;
        $voiceDiary->save();

        $data = [
            'channel_id'    => $voiceDiary->hash
        ];

        event(new DeclineVoiceCall($voiceDiary->caller_id, $data));
        event(new DeclineVoiceCall($voiceDiary->receiver_id, $data));

        CheckVoiceCallOutdated::removeVoice($voiceDiary);

        $this->createPostForDeclineCall($voiceDiary);

        return $voiceDiary;
    }

    public function pairCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->started_time = now();
        $voiceDiary->status = Consts::VOICE_STATUS_PAIRED;
        $voiceDiary->save();

        $data = [
            'channel_id'    => $voiceDiary->hash,
            'started_time'  => $voiceDiary->started_time
        ];

        event(new PairedVoiceCall($voiceDiary->caller_id, $data));
        event(new PairedVoiceCall($voiceDiary->receiver_id, $data));

        CheckVoiceCallOutdated::removeVoice($voiceDiary);

        return $voiceDiary;
    }

    public function endCall($hashVoiceChannel)
    {
        $voiceDiary = $this->getVoiceDiaryByHashVoiceChannel($hashVoiceChannel);

        $voiceDiary->ended_time = now();
        $voiceDiary->status = Consts::VOICE_STATUS_ENDED_CALL;
        $voiceDiary->save();

        $this->createPostForEndCall($voiceDiary);

        return $voiceDiary;
    }

    private function createPostForEndCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);
        if (Auth::check() && $caller->id !== Auth::id()) {
            return;
        }

        $data = $this->buildPostDefaultData($voiceDiary, $caller);
        $data['message'] = Consts::VOICE_AUDIO_CALL;
        $data['props']['time'] = $this->calculateTimeCalling($voiceDiary);
        $data['props']['status'] = Consts::VOICE_STATUS_ENDED_CALL;

        $this->createPost($data);
    }

    private function createPostForDeclineCall($voiceDiary)
    {
        $caller = $this->getUserInfo($voiceDiary->caller_id);

        $data = $this->buildPostDefaultData($voiceDiary, $caller);
        $data['message'] = Consts::VOICE_MISSED_CALL;
        $data['props']['missed_call_time'] = Carbon::parse($voiceDiary->updated_at)->timestamp * 1000;
        $data['props']['status'] = Consts::VOICE_STATUS_DECLINE;

        $this->createPost($data);
    }

    private function createPost($data)
    {
        $chatService = new ChatService;
        $chatService->createPost($data);
    }

    private function buildPostDefaultData($voiceDiary, $caller)
    {
        $channel = Channel::find($voiceDiary->channel_id);

        $data = [
            'channel_id' => $channel->mattermost_channel_id,
            'props' => [
                'type' => Consts::MESSAGE_PROPS_TYPE_VOICE,
                'caller_id' => $caller->id,
            ],
            'temp_id' => uniqid(),
            'login_id' => $caller->email,
            'user_id' => $caller->id
        ];

        return $data;
    }

    private function calculateTimeCalling($voiceDiary)
    {
        return Carbon::parse($voiceDiary->ended_time)->diffInSeconds($voiceDiary->started_time);
    }

    private function getVoiceDiaryByHashVoiceChannel($hashVoiceChannel)
    {
        $voiceDiary = VoiceDiary::where('hash', $hashVoiceChannel)->first();
        if (!$voiceDiary) {
            $this->throwVoiceChannelInvalid();
        }

        return $voiceDiary;
    }

    private function getUserInfo($userId)
    {
        $user = DB::table('users')
            ->join('user_settings', 'user_settings.id', 'users.id')
            ->leftJoin('user_rankings', 'user_rankings.user_id', 'users.id')
            ->select('users.*', 'user_settings.online', 'user_rankings.ranking_id')
            ->where('users.id', $userId)
            ->first();

        if (!$user) {
            $this->throwUserInvalid($username);
        }

        return (object) [
            'id'                => $user->id,
            'username'          => $user->username,
            'email'             => $user->email,
            'sex'               => $user->sex,
            'avatar'            => $user->avatar,
            'user_type'         => $user->user_type,
            'ranking_id'        => $user->ranking_id,
            'setting'           => [
                'online' => $user->online
            ]
        ];
    }

    private function throwUserInvalid ($username)
    {
        throw new Exception("Something wrong! The user <{$username}> is invalid.");
    }

    private function throwVoiceChannelInvalid ()
    {
        throw new InvalidVoiceChannelException();
    }

    private function throwFollowOrChatInvalid()
    {
        throw new InvalidVoiceChannelException('exceptions.voice_channel.follow_or_chat.invalid');
    }

    private function throwVoiceCallingInvalid($message)
    {
        throw new InvalidVoiceCallingException($message);
    }
}
