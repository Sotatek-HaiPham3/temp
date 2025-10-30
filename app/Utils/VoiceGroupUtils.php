<?php

namespace App\Utils;

use App\Consts;
use App\Models\Game;
use App\Models\VoiceChatRoom;
use App\Traits\RedisTrait;
use Illuminate\Support\Facades\Redis;

class VoiceGroupUtils {

    use RedisTrait;

    public static function saveShareVideoToCache($roomId, $video)
    {
        $videos = static::getAllShareVideoVideos();
        $videos[$roomId] = $video;

        return static::saveToCache(static::getShareVideoKey(), $videos);
    }

    public static function mergeShareVideo($rooms)
    {
        $videos = static::getAllShareVideoVideos();


        $rooms->getCollection()->transform(function($room) use ($videos) {
            $roomId = $room->id;

            if (array_key_exists($roomId, $videos)) {
                $room->video = $videos[$roomId];
            }

            return $room;
        });

        return $rooms;
    }

    public static function getShareVideoByRoomId($roomId)
    {
        $videos = static::getAllShareVideoVideos();

        if (array_key_exists($roomId, $videos)) {
            return $videos[$roomId];
        }

        return null;
    }

    public static function clearShareVideo($roomId)
    {
        $videos = static::getAllShareVideoVideos();

        if (array_key_exists($roomId, $videos)) {
            unset($videos[$roomId]);

            static::saveToCache(static::getShareVideoKey(), $videos);
        }

        return $videos;
    }

    public static function getAllShareVideoVideos()
    {
        $key = static::getShareVideoKey();

        if (static::hasKeyInCache($key)) {
            return static::getFromCache($key) ?? [];
        }

        return [];
    }

    private static function getShareVideoKey()
    {
        return 'voice-group:share-video';
    }

    private static function getRedisConnection()
    {
        return 'default';
    }

    public static function buildTitle($gameId, $type) {
        switch ($type) {
            case Consts::CATEGORY_TYPE_CHAT:
                $titleGamePrefix = 'Just Chatting Room';
                break;
            case Consts::CATEGORY_TYPE_COMMUNITY:
                $titleGamePrefix = 'Community Voice Room';
                break;
            default:
                $game = Game::find($gameId);
                $titleGamePrefix = $game->title;
                break;
        }

        $titleGame = $titleGamePrefix . " #1";
        $checkTitle = VoiceChatRoom::where('title', $titleGame )->where('status', '<>', Consts::VOICE_ROOM_STATUS_ENDED)->exists();
        if(!$checkTitle){
            return $titleGame;
        }

        $numericalPrefix = 1;
        while(true){
            //Check if title with final prefix exists.
            $newTitle = $titleGamePrefix . " #" . $numericalPrefix++;
            $checkTitle = VoiceChatRoom::where('title', $newTitle)->where('status', '<>', Consts::VOICE_ROOM_STATUS_ENDED)->exists(); //Check if already exists in DB

            if(!$checkTitle){
                return $newTitle;
                break;
            }
        }
    }
}
