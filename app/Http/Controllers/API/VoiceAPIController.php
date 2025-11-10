<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
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
    *   path="/create-channel",
    *   summary="Create a Channel",
    *   tags={"Voice Channel"},
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
    *   path="/join-channel",
    *   summary="Joining a Channel",
    *   tags={"Voice Channel"},
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
    *   path="/decline-call",
    *   summary="Decline Calling",
    *   tags={"Voice Channel"},
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
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/ending-call",
    *   summary="Ending Calling",
    *   tags={"Voice Channel"},
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
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/pairing-call",
    *   summary="Pairing Calling",
    *   tags={"Voice Channel"},
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
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/check-incoming-voice-call",
    *   summary="Check Incoming Voice Call",
    *   tags={"Voice Channel"},
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
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
