<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateVoiceChatRoomRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Services\VoiceService;
use Exception;

class VoiceAPIController extends AppBaseController
{

    private $voiceService;

    public function __construct(VoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice/create-channel",
    *   summary="Create a Channel",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="username",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function createChannel(Request $request)
    {
        $request->validate([
            'username' => 'required|exists_username'
        ]);

        $username = $request->username;

        DB::beginTransaction();
        try {
            $data = $this->voiceService->createChannel($username);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice/join-channel",
    *   summary="Joining a Channel",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function joinChannel(Request $request)
    {
        $request->validate([
            'channel_id' => 'required'
        ]);

        $voiceChannel = $request->channel_id;

        DB::beginTransaction();
        try {
            $data = $this->voiceService->joinChannel($voiceChannel);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice/decline-call",
    *   summary="Decline Calling",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function declineCall(Request $request)
    {
        $request->validate([
            'channel_id' => 'required'
        ]);

        $voiceChannel = $request->channel_id;

        DB::beginTransaction();
        try {
            $data = $this->voiceService->declineCall($voiceChannel);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice/ending-call",
    *   summary="Ending Calling",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function endCall(Request $request)
    {
        $request->validate([
            'channel_id' => 'required'
        ]);

        $voiceChannel = $request->channel_id;

        DB::beginTransaction();
        try {
            $data = $this->voiceService->endCall($voiceChannel);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice/pairing-call",
    *   summary="Pairing Calling",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function pairCall(Request $request)
    {
        $request->validate([
            'channel_id' => 'required'
        ]);

        $voiceChannel = $request->channel_id;

        DB::beginTransaction();
        try {
            $data = $this->voiceService->pairCall($voiceChannel);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice/check-incoming-voice-call",
    *   summary="Check Incoming Voice Call",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkIncomingVoiceCall(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->fireEventIncomingVoiceCallIfNeed($request->user_id);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    // Voice Group

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/create",
    *   summary="Create voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="is_private",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="type",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="size",in="formData",type="string"),
    *   @SWG\Parameter(name="title",in="formData",type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="topic",in="formData",type="string"),
    *   @SWG\Parameter(name="topic_category",in="formData",type="integer"),
    *   @SWG\Parameter(name="username",in="formData",type="string"),
    *   @SWG\Parameter(name="code",in="formData",type="string"),
    *   @SWG\Parameter(name="friend_id",in="formData",type="integer"),
    *   @SWG\Parameter(name="community_id",required=false,in="formData",type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function createVoiceChatRoom(CreateVoiceChatRoomRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->voiceService->createVoiceChatRoom($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/force-create",
    *   summary="Force create voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="is_private",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="type",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="size",in="formData",type="string"),
    *   @SWG\Parameter(name="title",in="formData",type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="topic",in="formData",type="string"),
    *   @SWG\Parameter(name="topic_category",in="formData",type="integer"),
    *   @SWG\Parameter(name="username",in="formData",type="string"),
    *   @SWG\Parameter(name="code",in="formData",type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function forceCreateRoom(CreateVoiceChatRoomRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->voiceService->forceCreateRoom($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/update",
    *   summary="Update voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="is_private",in="formData",type="integer"),
    *   @SWG\Parameter(name="size",in="formData",type="string"),
    *   @SWG\Parameter(name="title",in="formData",type="string"),
    *   @SWG\Parameter(name="topic",in="formData",type="string"),
    *   @SWG\Parameter(name="username",in="formData",type="string"),
    *   @SWG\Parameter(name="code",in="formData",type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function updateVoiceChatRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'title' => 'nullable|max:64',
            'topic' => 'nullable|max:128',
            'username' => 'nullable|min:3|max:50'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->updateVoiceChatRoom($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\POST(
    *   path="/v1/voice-room/check-room-available",
    *   summary="Check room available to join",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="invitation_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkRoomAvailable(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->checkRoomAvailable($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\POST(
    *   path="/v1/voice-room/check-user-can-join-room",
    *   summary="Check user can join room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="invitation_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUserCanJoinRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->checkUserCanJoinRoom($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\POST(
    *   path="/v1/voice-room/check-user-can-create-room",
    *   summary="Check user can create room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="invitation_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUserCanCreateRoom(Request $request)
    {
        $request->validate([
            'title' => 'nullable|max:64',
            'topic' => 'nullable|max:128',
            'username' => 'nullable|min:3|max:50'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->checkUserInOtherRoom();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/join",
    *   summary="Join voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="invitation_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function joinVoiceChatRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->joinVoiceChatRoom($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/force-join",
    *   summary="Force Join Room Over Platform",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="invitation_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function forceJoinRoom(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->forceJoinRoom($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/invite",
    *   summary="Invite user into chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function inviteUserIntoRoom(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->inviteUserIntoRoom($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/close",
    *   summary="Close voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function closeRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->closeRoom($request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/leave",
    *   summary="Leave voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function leaveVoiceChatRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->leaveVoiceChatRoom($request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

     /**
    * @SWG\Put(
    *   path="/v1/voice-room/leave-anyroom",
    *   summary="Leave any current room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function leaveAnyRoom(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->voiceService->leaveAnyRoom();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/list-category",
    *   summary="Get list voice category",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function listVoiceCategory(Request $request)
    {
        try {
            $data = $this->voiceService->listVoiceCategory($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/list",
    *   summary="Get list voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="query",required=false,type="integer"),
    *   @SWG\Parameter(name="type",in="query",required=false,type="string"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *   @SWG\Parameter(name="page",in="query",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function listVoiceChatRoom(Request $request)
    {
        try {
            $data = $this->voiceService->listVoiceChatRoom($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/kick",
    *   summary="Kick user out voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function kickUserOutRoom(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->kickUserOutRoom($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/detail",
    *   summary="Get detail voice chat room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getRoomDetail(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name'
        ]);

        try {
            $data = $this->voiceService->getRoomDetail($request->name);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/room-users",
    *   summary="Get room users",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="name",in="formData",required=true,type="string"),
    *   @SWG\Parameter(name="type",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="limit",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getRoomUsers(Request $request)
    {
        $request->validate([
            'name' => 'required|exists:voice_chat_rooms,name'
        ]);

        try {
            $data = $this->voiceService->getRoomUsers($request->name, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/make-host",
    *   summary="Make user become a host",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function makeRoomHost(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->makeHost($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/make-moderator",
    *   summary="Make guest become a moderator",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function makeRoomModerator(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->makeModerator($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/remove-moderator",
    *   summary="Make moderator become a guest",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function removeRoomModerator(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->removeModerator($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/make-speaker",
    *   summary="Make guest become a speaker",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function makeRoomSpeaker(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->makeSpeaker($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/remove-speaker",
    *   summary="Make speaker become a guest",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function removeRoomSpeaker(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->removeSpeaker($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/voice-room/update-username",
    *   summary="Update user username in room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="username",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function updateUserUsername(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'username' => 'required|min:3|max:50'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->updateUserUsername($request->room_id, $request->username);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/get-invite-list",
    *   summary="Get Invite List For Room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getInviteList(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->getInviteList($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/check-next-room",
    *   summary="Check Next Room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkNextRoom(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:room_categories,game_id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->checkNextRoom($request->game_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/check-random-room",
    *   summary="Check Random Room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="type",in="formData",required=false,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkRandomRoom(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:room_categories,game_id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->checkRandomRoom($request->game_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/raise-hand",
    *   summary="Raise Hand",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="message",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function raiseHand(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->raiseHand($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/list-raise-hand",
    *   summary="List Raise Hand",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function listRaiseHand(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
        ]);

        try {
            $data = $this->voiceService->listRaiseHand($request->room_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/report-room",
    *   summary="Report Room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="reason_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="details",in="formData",required=false,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function reportRoom(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'reason_id' => 'required',
            'details' => 'nullable|string|max:500|min:24'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->reportRoom($request->room_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/report-user",
    *   summary="Report User",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="reason_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="reported_user",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="details",in="formData",required=false,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function reportUser(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'reported_user' => 'required',
            'reason_id' => 'required',
            'details' => 'nullable|string|max:500|min:24'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->reportUser($request->room_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/check-room-report-existed",
    *   summary="Check Room Report Existed",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkRoomReportExisted(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
        ]);

        try {
            $data = $this->voiceService->checkRoomReportExisted($request->room_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/check-user-report-existed",
    *   summary="Check User Report Existed",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Parameter(name="reported_user",in="query",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUserReportExisted(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'reported_user' => 'required',
        ]);

        try {
            $data = $this->voiceService->checkUserReportExisted($request->room_id, $request->reported_user);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/get-user-current-room",
    *   summary="Get user current room room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getCurrentRoom(Request $request)
    {
        try {
            $data = $this->voiceService->getCurrentRoom();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/ask-question",
    *   summary="Ask question in AMA room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function askQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'question' => 'required|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->askQuestion($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/voice-room/questions",
    *   summary="Get AMA room questions",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Parameter(name="include_answering",in="query",required=false,type="boolean"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getRoomQuestions(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->getRoomQuestions($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/reject-question",
    *   summary="Reject question in AMA room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function rejectQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'question_id' => 'required|exists:room_questions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->rejectQuestion($request->room_id, $request->question_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/accept-question",
    *   summary="Accept question in AMA room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function acceptQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'question_id' => 'required|exists:room_questions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->acceptQuestion($request->room_id, $request->question_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/switch-allow-question",
    *   summary="Switch Allow Question in AMA room",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="allow_question",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function switchAllowQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'allow_question' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->switchAllowQuestion($request->room_id, $request->allow_question);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/category-existed",
    *   summary="Check category existed",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="slug",in="formData",required=true,type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkCategoryExisted(Request $request)
    {
        $request->validate([
            'slug' => 'required'
        ]);

        try {
            $data = $this->voiceService->checkCategoryExisted($request->slug);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/voice-room/share-video",
    *   summary="Share youtube or twitch video",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="info",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="room_id"),
    *           @SWG\Property(
    *               type="array",
    *               property="video",
    *               @SWG\Items(
    *                   @SWG\Property(property="title", type="string", description="Title of video"),
    *                   @SWG\Property(property="type", type="string", description="Type of video"),
    *               ),
    *           ),
    *       ),
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function shareVideo(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'video.title' => 'required',
            'video.type' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->shareVideo($request->room_id, $request->video);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/v1/voice-room/clear-share-video",
    *   summary="Clear share youtube or twitch video",
    *   tags={"V1.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function clearShareVideo(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->clearShareVideo($request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
