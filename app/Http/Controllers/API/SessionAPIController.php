<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\SessionService;
use App\Http\Requests\BookGameProfileRequest;
use App\Models\Session;
use App\Consts;
use DB;
use Auth;
use Exception;

class SessionAPIController extends AppBaseController
{
    protected $sessionService;

    public function __construct(SessionService $sessionService)
    {
        $this->sessionService = $sessionService;
    }

    /**
    * @SWG\Post(
    *   path="/user/session/book",
    *   summary="Book a Game Profile",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="game_profile_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="type", in="formData", required=true, type="string", enum={"hour","per_game"}),
    *   @SWG\Parameter(name="quantity", in="formData", required=true, type="number"),
    *   @SWG\Parameter(name="schedule", in="formData", required=false, type="string"),
    *   @SWG\Parameter(name="timeoffset", in="formData", required=true, type="number"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function bookGameProfile(BookGameProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->sessionService->bookGameProfile($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/session/check-book-another-gamelancer",
    *   summary="Check user booked another gamelancer",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="game_profile_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkBookingAnotherGamelancer(Request $request)
    {
        $request->validate([
            'game_profile_id' => 'required|exists:game_profiles,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->checkBookingAnotherGamelancer($request->game_profile_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/session/check-gamelancer-offline",
    *   summary="Check gamelancer offline",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="game_profile_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="type", in="formData", required=true, type="string", enum={"hour","per_game"}),
    *   @SWG\Parameter(name="quantity", in="formData", required=true, type="number"),
    *   @SWG\Parameter(name="schedule", in="formData", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkGamelancerOffline(Request $request)
    {
        $pattern = Consts::SESSION_SCHEDULE_AT_DATETIME_FORMAT;
        $request->validate([
            'game_profile_id' => 'required|exists:game_profiles,id',
            'type'            => [Rule::in([Consts::GAME_TYPE_HOUR, Consts::GAME_TYPE_PER_GAME])],
            'quantity'        => 'required|numeric|gte:0.5',
            'schedule'        => "date|date_format:{$pattern}|after:now|nullable"
        ]);

        try {
            $data = $this->sessionService->checkGamelancerOffline($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/accept-booking",
    *   summary="Accept Booking Game Profile",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function acceptBookingGameProfile(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->acceptBookingGameProfile($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/resject-booking",
    *   summary="Reject Booking Game Profile",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function rejectBookingGameProfile(Request $request)
    {
        $session = Session::where('id', $request->session_id)->first();
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'content'    => $request->reason_id || $this->sessionService->isFreeSession($session) ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->rejectBookingGameProfile($request->session_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/ready",
    *   summary="Ready Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function readySession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->readySession($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/session/add-request",
    *   summary="Add More Hour or Game Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="quantity", in="formData", required=true, type="number"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function addSessionRequest(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'quantity' => 'required|numeric|gte:5'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->addSessionRequest($request->session_id, $request->quantity);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/accept-request",
    *   summary="Accept Add Request Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="request_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function acceptAddingRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:session_adding_requests,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->acceptAddingRequest($request->request_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/reject-request",
    *   summary="Reject Add Request Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="request_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function rejectAddingRequest(Request $request)
    {
        $request->validate([
            'request_id' => 'required|exists:session_adding_requests,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->rejectAddingRequest($request->request_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/stop",
    *   summary="Stop Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function stopSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'content'   => $request->reason_id ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->stopSession($request->session_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/complete",
    *   summary="Complete Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function completeSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->completeSession($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/user/session/booked-slots",
     *   summary="Get Scheduler Booked Slots. It will response schedule_at (UTC)",
     *   tags={"Sessions"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSessionBookedSlots(Request $request)
    {
        $userId = Auth::id();
        $data = $this->sessionService->getSessionBookedSlots($userId);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/user/session/booked-slots-as-user",
     *   summary="Get Scheduler Booked Slots As A User (to book sessions of another gamelancer). It will response schedule_at (UTC)",
     *   tags={"Sessions"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSessionBookedSlotsAsUser(Request $request)
    {
        $userId = Auth::id();
        $data = $this->sessionService->getSessionBookedSlotsAsUser($userId);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/user/session/playing-session",
     *   summary="Get Playing Session With Another User",
     *   tags={"Sessions"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="partner_id", in="formData", required=true, type="integer"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getPlayingSessionPairUser(Request $request)
    {
        $request->validate([
            'partner_id' => 'required'
        ]);

        $data = $this->sessionService->getPlayingSessionPairUser($request->partner_id);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/session/review",
    *   summary="Review Session",
    *   tags={"Sessions"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="review",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="session_id"),
    *           @SWG\Property(type="number", property="rate"),
    *           @SWG\Property(type="string", property="description"),
    *           @SWG\Property(type="integer", property="recommend", enum={"image","video"}),
    *           @SWG\Property(
    *               type="array",
    *               property="tags",
    *               @SWG\Items(type="number", description="Review Tag ID"),
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function reviewSession(Request $request)
    {
        $request->validate([
            'session_id'        => 'required|exists:sessions,id',
            'rate'              => 'required|between:1,5',
            'description'       => 'max:500',
            'recommend'         => [Rule::in([Consts::TRUE, Consts::FALSE]), 'nullable'],
            'tags'              => 'array'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->reviewSession($request->session_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/cancel-booking",
    *   summary="Cancel Book Now Session",
    *   tags={"Sessions"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function cancelBooking(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->cancelBooking($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/session/restart",
    *   summary="Restart Session",
    *   tags={"Sessions"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="timeoffset", in="formData", required=true, type="number"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function restartSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->restartSession($request->session_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/mark-as-complete",
    *   summary="Mark As Complete Session",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function markAsComplete(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->markAsComplete($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/reject-mark-complete",
    *   summary="Reject Mark Complete Session",
    *   tags={"Sessions"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function rejectMarkComplete(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id',
            'content'    => $request->reason_id ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->rejectMarkComplete($request->session_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/session/continue-session",
    *   summary="Continue Session",
    *   tags={"Sessions"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="session_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function continueSession(Request $request)
    {
        $request->validate([
            'session_id' => 'required|exists:sessions,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->sessionService->continueSession($request->session_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/session/data-bubble",
    *   summary="Get User Data Bubble chat",
    *   tags={"Sessions"},
    *   security={{"passport": {}},},
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getDataBubbleChat(Request $request)
    {
        $ids = json_decode(base64url_decode($request->ids), true);
        try {
            $data = $this->sessionService->getDataBubbleChat($ids ?: []);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
