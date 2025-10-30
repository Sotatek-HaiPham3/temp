<?php

namespace App\Http\Controllers\API\V2;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateVoiceChatRoomRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Services\VoiceService;
use Exception;
use App\Consts;

class VoiceAPIController extends AppBaseController
{

    private $voiceService;

    public function __construct(VoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }
    // Voice Group

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/create",
    *   summary="Create voice chat room",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->createVoiceChatRoomV2($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v2/voice-room/list",
    *   summary="Get list voice chat room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="query",required=false,type="integer"),
    *   @SWG\Parameter(name="community_id",in="query",required=false,type="integer"),
    *   @SWG\Parameter(name="type",in="query",required=false,type="string"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *   @SWG\Parameter(name="page",in="query",required=false,type="integer"),
     *  @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *              @SWG\Property(type="integer", property="current_page"),
     *              @SWG\Property(type="integer", property="total"),
     *              @SWG\Property(type="integer", property="last_page"),
     *              @SWG\Property(type="integer", property="per_page"),
     *              @SWG\Property(type="integer", property="total_users"),
     *               @SWG\Property(type="array", property="data",
     *                   @SWG\Items(
     *                      @SWG\Property(property="background_url", type="integer"),
     *                      @SWG\Property(property="code", type="string"),
     *                      @SWG\Property(property="community_id", type="integer"),
     *                      @SWG\Property(property="created_at", type="string"),
     *                      @SWG\Property(property="creator_id", type="integer"),
     *                      @SWG\Property(property="current_size", type="integer"),
     *                      @SWG\Property(property="game_id", type="integer"),
     *                      @SWG\Property(property="id", type="integer"),
     *                      @SWG\Property(property="is_private", type="integer"),
     *                      @SWG\Property(property="name", type="string"),
     *                      @SWG\Property(property="pinned", type="integer"),
     *                      @SWG\Property(property="region", type="string"),
     *                      @SWG\Property(property="rules", type="string"),
     *                      @SWG\Property(property="size", type="integer"),
     *                      @SWG\Property(property="status", type="string"),
     *                      @SWG\Property(property="title", type="string"),
     *                      @SWG\Property(property="topic", type="string"),
     *                      @SWG\Property(property="topic_id", type="integer"),
     *                      @SWG\Property(property="type", type="string"),
     *                      @SWG\Property(property="updated_at", type="string"),
     *                      @SWG\Property(property="user_id", type="integer"),
     *                      @SWG\Property(property="user", type="object",
     *                        @SWG\Property(property="id", type="integer"),
     *                        @SWG\Property(property="email", type="string"),
     *                        @SWG\Property(property="username", type="string"),
     *                        @SWG\Property(property="sex", type="integer"),
     *                        @SWG\Property(property="user_type", type="integer"),
     *                        @SWG\Property(property="avatar", type="string"),
     *                        @SWG\Property(property="existsCreditCard", type="boolean"),
     *                        @SWG\Property(property="newEmail", type="string"),
     *                        @SWG\Property(property="newUsername", type="string"),
     *                        @SWG\Property(property="newPhoneNumber", type="string"),
     *                        @SWG\Property(property="isFirstLogin", type="boolean"),
     *                        @SWG\Property(property="isGamelancer", type="boolean"),
     *                        @SWG\Property(property="is_vip", type="boolean"),
     *                      )
     *                  ),
     *             ),
     *          )
     *       )
     *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function listVoiceChatRoom(Request $request)
    {
        try {
            $data = $this->voiceService->listVoiceChatRoomV2($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v2/voice-room/get-invite-list",
    *   summary="Get Invite List For Room",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->getInviteListV2($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/voice-room/force-create",
     *   summary="Force create voice chat room",
     *   tags={"V1.Voice Channel"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="community_id",in="formData",required=false,type="integer"),
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
            $data = $this->voiceService->forceCreateRoomV2($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/join",
    *   summary="Join voice chat room",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->joinVoiceChatRoomV2($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/force-join",
    *   summary="Force Join Room Over Platform",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->forceJoinRoomV2($request->name, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/check-next-room",
    *   summary="Check Next Room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="community_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkNextRoom(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:room_categories,game_id',
            'community_id' => $request->game_id === Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID ? 'required' : ''
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
    *   path="/v2/voice-room/check-random-room",
    *   summary="Check Random Room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="type",in="formData",required=false,type="string"),
    *   @SWG\Parameter(name="community_id",in="formData",required=false,type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkRandomRoom(Request $request)
    {
        $request->validate([
            'game_id' => 'required|exists:room_categories,game_id',
            'community_id' => $request->game_id === Consts::COMMUNITY_ROOM_CATEGORY_GAME_ID ? 'required' : ''
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
    * @SWG\Put(
    *   path="/v2/voice-room/make-host",
    *   summary="Make user become a host",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->makeHostV2($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v2/voice-room/make-moderator",
    *   summary="Make guest become a moderator",
    *   tags={"V2.Voice Channel"},
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
            $data = $this->voiceService->makeModeratorV2($request->user_id, $request->room_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/question/cancel",
    *   summary="Cancel question in AMA room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="Success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="object",
    *                @SWG\Property(property="id", type="integer", default=1),
    *                @SWG\Property(property="acceptor_id", type="integer", default=1),
    *                @SWG\Property(property="rejector_id", type="integer", default=1),
    *                @SWG\Property(property="room_id", type="integer", default=1),
    *                @SWG\Property(property="user_id", type="integer", default=1),
    *                @SWG\Property(property="question", type="string"),
    *                @SWG\Property(property="status", type="string"),
    *                @SWG\Property(property="created_at", type="string"),
    *                @SWG\Property(property="updated_at", type="string"),
    *          ),
    *       )
    *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function cancelQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'question_id' => 'required|exists:room_questions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->cancelQuestion($request->room_id, $request->question_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/question/accept",
    *   summary="Accept question in AMA room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="Success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="object",
    *                @SWG\Property(property="id", type="integer", default=1),
    *                @SWG\Property(property="acceptor_id", type="integer", default=1),
    *                @SWG\Property(property="rejector_id", type="integer", default=1),
    *                @SWG\Property(property="room_id", type="integer", default=1),
    *                @SWG\Property(property="user_id", type="integer", default=1),
    *                @SWG\Property(property="question", type="string"),
    *                @SWG\Property(property="status", type="string"),
    *                @SWG\Property(property="created_at", type="string"),
    *                @SWG\Property(property="updated_at", type="string"),
    *                @SWG\Property(property="user", type="object",
    *                     @SWG\Property(property="id", type="integer"),
    *                     @SWG\Property(property="username", type="string"),
    *                     @SWG\Property(property="sex", type="integer"),
    *                     @SWG\Property(property="user_type", type="integer"),
    *                     @SWG\Property(property="avatar", type="string"),
    *                     @SWG\Property(property="is_vip", type="boolean"),
    *                     @SWG\Property(property="online_setting", type="boolean"),
    *                )
    *          ),
    *       )
    *   ),
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
            $data = $this->voiceService->acceptQuestionV2($request->room_id, $request->question_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v2/voice-room/question/answer",
    *   summary="Answer question in AMA room",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="formData",required=true,type="integer"),
    *   @SWG\Parameter(name="question_id",in="formData",required=true,type="integer"),
    *   @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="Success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="object",
    *                @SWG\Property(property="acceptor_id", type="integer", default=1),
    *                @SWG\Property(property="created_at", type="string"),
    *                @SWG\Property(property="id", type="integer", default=1),
    *                @SWG\Property(property="question", type="string"),
    *                @SWG\Property(property="rejector_id", type="integer", default=1),
    *                @SWG\Property(property="room_id", type="integer", default=1),
    *                @SWG\Property(property="status", type="string"),
    *                @SWG\Property(property="updated_at", type="string"),
    *                @SWG\Property(property="user_id", type="integer", default=1),
    *          ),
    *       )
    *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function answerQuestion(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id',
            'question_id' => 'required|exists:room_questions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->voiceService->answerQuestion($request->room_id, $request->question_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v2/voice-room/queue-questions",
    *   summary="Get AMA queued questions",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *  @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="object",
    *              @SWG\Property(type="integer", property="current_page"),
    *              @SWG\Property(type="integer", property="total"),
    *              @SWG\Property(type="integer", property="last_page"),
    *              @SWG\Property(type="integer", property="per_page"),
    *              @SWG\Property(type="array", property="data",
    *                  @SWG\Items(
    *                      @SWG\Property(property="acceptor_id", type="integer"),
    *                      @SWG\Property(property="created_at", type="string"),
    *                      @SWG\Property(property="id", type="integer"),
    *                      @SWG\Property(property="question", type="string"),
    *                      @SWG\Property(property="rejector_id", type="integer"),
    *                      @SWG\Property(property="room_id", type="integer"),
    *                      @SWG\Property(property="status", type="string"),
    *                      @SWG\Property(property="updated_at", type="string"),
    *                      @SWG\Property(property="user_id", type="integer"),
    *                      @SWG\Property(property="user", type="object",
    *                        @SWG\Property(property="avatar", type="string"),
    *                        @SWG\Property(property="id", type="integer"),
    *                        @SWG\Property(property="sex", type="string"),
    *                        @SWG\Property(property="username", type="string"),
    *                      )
    *                 )
    *             ),
    *          )
    *       )
    *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getQueuedQuestions(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->getQueuedQuestions($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v2/voice-room/asked-questions",
    *   summary="Get AMA asked questions",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *   @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="object",
    *              @SWG\Property(type="integer", property="current_page"),
    *              @SWG\Property(type="integer", property="total"),
    *              @SWG\Property(type="integer", property="last_page"),
    *              @SWG\Property(type="integer", property="per_page"),
    *              @SWG\Property(type="array", property="data",
    *                  @SWG\Items(
    *                      @SWG\Property(property="acceptor_id", type="integer"),
    *                      @SWG\Property(property="created_at", type="string"),
    *                      @SWG\Property(property="id", type="integer"),
    *                      @SWG\Property(property="question", type="string"),
    *                      @SWG\Property(property="rejector_id", type="integer"),
    *                      @SWG\Property(property="room_id", type="integer"),
    *                      @SWG\Property(property="status", type="string"),
    *                      @SWG\Property(property="updated_at", type="string"),
    *                      @SWG\Property(property="user_id", type="integer"),
    *                      @SWG\Property(property="user", type="object",
    *                        @SWG\Property(property="avatar", type="string"),
    *                        @SWG\Property(property="id", type="integer"),
    *                        @SWG\Property(property="sex", type="string"),
    *                        @SWG\Property(property="username", type="string"),
    *                      )
    *                 )
    *             ),
    *          )
    *       )
    *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getAskedQuestions(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->getAskedQuestions($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v2/voice-room/questions",
    *   summary="Get AMA questions",
    *   tags={"V2.Voice Channel"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
    *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
    *   @SWG\Response(
    *       response=200,
    *       description="Success",
    *       @SWG\Schema(
    *            title="success",
    *            @SWG\Property(property="success", type="boolean"),
    *            @SWG\Property(property="dataVersion", type="string"),
    *            @SWG\Property(property="data", type="array",
    *                @SWG\Items(
    *                    @SWG\Property(property="acceptor_id", type="integer"),
    *                    @SWG\Property(property="created_at", type="string"),
    *                    @SWG\Property(property="id", type="integer"),
    *                    @SWG\Property(property="question", type="string"),
    *                    @SWG\Property(property="rejector_id", type="integer"),
    *                    @SWG\Property(property="room_id", type="integer"),
    *                    @SWG\Property(property="status", type="string"),
    *                    @SWG\Property(property="updated_at", type="string"),
    *                    @SWG\Property(property="user_id", type="integer"),
    *                    @SWG\Property(property="user", type="object",
    *                      @SWG\Property(property="avatar", type="string"),
    *                      @SWG\Property(property="id", type="integer"),
    *                      @SWG\Property(property="sex", type="string"),
    *                      @SWG\Property(property="username", type="string"),
    *                    )
    *                ),
    *          )
    *       )
    *   ),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getQuestions(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->getQuestions($request->room_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/voice-room/count-questions",
     *   summary="Get AMA asked questions",
     *   tags={"V2.Voice Channel"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="room_id",in="query",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="total_asked", type="integer"),
     *                @SWG\Property(property="total_queued", type="integer")
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function countQuestions(Request $request)
    {
        $request->validate([
            'room_id' => 'required|exists:voice_chat_rooms,id'
        ]);

        try {
            $data = $this->voiceService->countQuestions($request->room_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
