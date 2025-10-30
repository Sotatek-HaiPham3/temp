<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\VoiceService;
use DB;

class EchoHookAPIController extends Controller
{
    const EVENT_JOIN        = 'join';
    const EVENT_LEAVE       = 'leave';
    const PREFIX_PRESENT    = 'presence-VoiceRoom.';

    private $voiceService;

    public function __construct(VoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    public function handleHook(Request $request)
    {
        DB::beginTransaction();
        try {
            $event      = $request->event;
            $channel    = $request->channel;
            $payload    = $request->payload;

            if ($event === static::EVENT_JOIN) {
                return;
            }

            if ($this->isPresenceVoiceRoom($channel)) {
                $roomId = str_replace(static::PREFIX_PRESENT, '', $channel);
                $this->voiceService->leaveRoomForUserWithSid($payload['user_id'], $payload['sid'], $roomId);
            }

            DB::commit();

            return [];
        } catch (\Exception $e) {
            DB::rollback();

            logger()->error('===============ECHO_HOOK==============: ', [$e]);
        }
    }

    private function isPresenceVoiceRoom($channel)
    {
        return str_contains($channel, 'presence-VoiceRoom');
    }
}
