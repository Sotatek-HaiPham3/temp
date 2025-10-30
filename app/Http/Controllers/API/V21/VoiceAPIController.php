<?php

namespace App\Http\Controllers\API\V21;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateVoiceChatRoomRequest;
use App\Http\Services\VoiceService;
use Exception;
use Illuminate\Support\Facades\DB;

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
     *   path="/v2.1/voice-room/force-create",
     *   summary="Force create voice chat room",
     *   tags={"V2.1.Voice Channel"},
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
     *  @SWG\Parameter(
     *       name="friend_id[]",
     *       in="formData",
     *       description="array of friend user id invites to room",
     *       type="array",
     *       items={
     *          {"type":"string"}
     *       }
     *   ),
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
            $data = $this->voiceService->forceCreateRoomV21($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2.1/voice-room/create",
     *   summary="Create voice chat room",
     *   tags={"V2.1.Voice Channel"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="game_id",in="formData",type="integer"),
     *   @SWG\Parameter(name="is_private",in="formData",type="integer"),
     *   @SWG\Parameter(name="type",in="formData",type="string"),
     *   @SWG\Parameter(name="size",in="formData",type="string"),
     *   @SWG\Parameter(name="title",in="formData",type="string"),
     *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
     *   @SWG\Parameter(name="topic",in="formData",type="string"),
     *   @SWG\Parameter(name="topic_category",in="formData",type="integer"),
     *   @SWG\Parameter(name="username",in="formData",type="string"),
     *   @SWG\Parameter(name="code",in="formData",type="string"),
     *  @SWG\Parameter(
     *       name="friend_id[]",
     *       in="formData",
     *       description="array of friend user id invites to room",
     *       type="array",
     *       items={
     *          {"type":"string"}
     *       }
     *   ),
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
            $data = $this->voiceService->createVoiceChatRoomV21($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
