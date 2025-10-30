<?php

namespace App\Traits;

use App\Events\SessionSystemMessageUpdated;
use App\Models\SessionSystemMessage;
use App\Models\BountyClaimRequest;
use App\Models\Session;
use App\Models\SessionReviewTag;
use App\Models\Tip;
use App\Consts;
use Carbon\Carbon;
use App\Utils;
use App\Jobs\CalculateReviewTag;
use App\Events\SessionPlayingUpdated;

trait SessionTrait {

    public function createChatSystemMessage($session, $message, $processed = Consts::FALSE)
    {
        $data = $this->getSessionDetail($session->id);
        $systemMessage = SessionSystemMessage::create([
            'channel_id' => $session->channel->mattermost_channel_id,
            'sender_id' => $message['sender'],
            'object_id' => $session->id,
            'object_type' => Consts::OBJECT_TYPE_SESSION,
            'message_key' => $message['key'],
            'message_props' => $message['props'],
            'message_type' => $message['type'],
            'data' => $data,
            'is_processed' => $processed,
            'started_event' => Utils::currentMilliseconds()
        ]);

        return $systemMessage;
    }

    public function createPostMessage($channelId, $message, $systemMessage)
    {
        $messageParams = array();
        $messageParams['channel_id'] = $channelId;
        $messageParams['message'] = $message['key'];
        $messageParams['props'] = (object) [
            'system_message_id' => $systemMessage->id,
            'sender_id' => $message['sender']
        ];

        $postMessage = $this->chatService->createPostSystem($messageParams);

        $systemMessage = SessionSystemMessage::find($systemMessage->id);
        $systemMessage->started_event = $postMessage->create_at;
        $systemMessage->save();
    }

    public function createSessionReviewTags($review, $tags)
    {
        foreach ($tags as $tag) {
            SessionReviewTag::create([
                'review_id' => $review->id,
                'review_tag_id' => $tag
            ]);
            CalculateReviewTag::dispatch($review, $tag);
        }
    }

    public function eventSessionMessageUpdated($messageId, $userIds)
    {
        event(new SessionSystemMessageUpdated($messageId, $userIds[0]));
        event(new SessionSystemMessageUpdated($messageId, $userIds[1]));
    }

    public function fireSessionPlayingUpdated($session)
    {
        $data = Session::with(['gameProfile', 'gameOffer', 'pendingRequests'])
            ->select('sessions.*', 'channels.mattermost_channel_id')
            ->join('channels', 'sessions.channel_id', 'channels.id')
            ->where('sessions.id', $session->id)
            ->first();

        event(new SessionPlayingUpdated($data->claimer_id, $data));
        event(new SessionPlayingUpdated($data->gamelancer_id, $data));
    }

    public function getSessionDetail($sessionId)
    {
        return Session::with(['gameProfile', 'gameOffer', 'pendingRequests'])
            ->where('id', $sessionId)
            ->first();
    }
}
