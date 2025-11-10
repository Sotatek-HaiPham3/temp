<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AppBaseController as AppBase;
use App\Http\Services\UserService;
use App\Http\Requests\GamelancerInfoRequest;
use App\Http\Requests\AvailableTimesRequest;
use App\Http\Requests\ProfilePicture;
use App\Http\Requests\UserPhotoRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UserSettingRequest;
use App\Http\Requests\CreateAvailableTimesRequest;
use App\Http\Requests\ReportRequest;
use App\Http\Requests\InterestsGames;
use App\Events\UserOnline;
use App\Consts;
use App\Utils;
use DB;
use Exception;
use Illuminate\Support\Facades\Validator;
use Mail;
use App\Mails\ChangePasswordMail;
use Illuminate\Validation\Rule;

class UserAPIController extends AppBase
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService      = $userService;
    }

    /**
     * @SWG\Get(
     *   path="/user/profile",
     *   summary="Get User Profile",
     *   tags={"Users"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserProfile(Request $request)
    {
        try {
            $data = $this->userService->getUserProfile(Auth::id());
            event(new UserOnline($data->id));
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
     * @SWG\Get(
     *   path="/invitation-code/is-valid",
     *   summary="Validate Invitation Code",
     *   tags={"Users"},
     *   security={
      *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="invitation_code",
     *       in="path",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function validateInvitationCode(Request $request)
    {
        $request->validate([
            'invitation_code' => 'nullable|valid_invitation_code'
        ]);

        return [];
    }

    /**
     * @SWG\Get(
     *   path="/user/balance",
     *   summary="Get User Balance",
     *   tags={"Users"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserBalance(Request $request)
    {
        try {
            $data = $this->userService->getUserBalances(Auth::id());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/become-gamelancer",
     *   summary="Submit Gamelancer Information",
     *   tags={"Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(
     *       name="gamelancer_info",
     *       in="body",
     *       required=true,
     *       @SWG\Schema(
     *           @SWG\Property(type="string", property="social_id", description="Social ID"),
     *           @SWG\Property(type="string", property="social_type", description="Social Type"),
     *           @SWG\Property(type="string", property="total_hours", description="Total Available Time"),
     *           @SWG\Property(type="string", property="introduction", description="Introduction"),
     *           @SWG\Property(type="string", property="invitation_code", description="Invitation Code"),
     *           @SWG\Property(type="number", property="timeoffset", description="Client time offset"),
     *           @SWG\Property(
     *               type="array",
     *               property="available_times",
     *               @SWG\Items(
     *                   @SWG\Property(property="weekday", type="number", enum={0,1,2,3,4,5,6}, description="Day of week in number"),
     *                   @SWG\Property(property="from", type="number", description="Times From in Minutes"),
     *                   @SWG\Property(property="to", type="number", description="Times To in Minutes")
     *               ),
     *           ),
     *           @SWG\Property(
     *               property="session",
     *               @SWG\Property(type="number", property="game_id", description="Game ID"),
     *               @SWG\Property(type="string", property="title", description="Game Profile Title"),
     *               @SWG\Property(type="string", property="description", description="Game Profile Description"),
     *               @SWG\Property(type="string", property="audio", description="Game Profile Audio"),
     *               @SWG\Property(
     *                  type="array",
     *                  property="offers",
     *                  @SWG\Items(
     *                      @SWG\Property(property="type", type="string", enum={"hour","per_game"}, description="Type of offer"),
     *                      @SWG\Property(property="quantity", type="number", description="Quantity of hours or games"),
     *                      @SWG\Property(property="price", type="number", description="Total price")
     *                  ),
     *               ),
     *               @SWG\Property(
     *                  type="array",
     *                  property="platform_ids",
     *                  @SWG\Items(type="number", description="Platform ID"),
     *               ),
     *               @SWG\Property(
     *                  type="array",
     *                  property="medias",
     *                  @SWG\Items(
     *                      @SWG\Property(property="type", type="string", enum={"image","video"}, description="Type of file"),
     *                      @SWG\Property(property="url", type="string", description="File url")
     *                  )
     *               )
     *           )
     *       )
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function createGamelancerInfo(GamelancerInfoRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->createGamelancerInfo($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/user-info/{username}",
     *   summary="Get User Information by Username",
     *   tags={"Users"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="username",
     *       in="path",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserInfoByUsername(Request $request, $username = null)
    {
        Validator::make(['username' => $username], ['username' => 'required'])->validate();

        $data = $this->userService->getUserInfoByUsername($username);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/available-times",
    *   summary="Get User Available Times",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="timeoffset",
    *       description="Client timeoffset",
    *       in="query",
    *       type="number"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getAvailableTimes(Request $request)
    {
        $request->validate([
            'timeoffset' => 'required|numeric'
        ]);

        $data = $this->userService->getAvailableTimes(Auth::id(), $request->timeoffset);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/available-times/add",
    *   summary="Add Available Times",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="weekday", in="formData", required=true, type="number", enum={0,1,2,3,4,5,6}, description="Day of week in number (from Sunday -> Saturday)"),
    *   @SWG\Parameter(name="from", in="formData", required=true, type="number", description="From time (unit: minutes)"),
    *   @SWG\Parameter(name="to", in="formData", required=true, type="number", description="To time (unit: minutes)"),
    *   @SWG\Parameter(name="all", in="formData", required=true, type="number", enum={0,1}, description="All Days: false|true"),
    *   @SWG\Parameter(name="timeoffset", in="formData", required=true, type="number", description="Client time offset"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function addAvailableTime(CreateAvailableTimesRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->addAvailableTime($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/user/available-times/delete",
    *   summary="Delete Available Times Item",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="weekday", in="formData", required=true, type="number", enum={0,1,2,3,4,5,6}, description="Day of week in number (from Sunday -> Saturday)"),
    *   @SWG\Parameter(name="from", in="formData", required=true, type="number", description="From time (unit: minutes)"),
    *   @SWG\Parameter(name="to", in="formData", required=true, type="number", description="To time (unit: minutes)"),
    *   @SWG\Parameter(name="timeoffset", in="formData", required=true, type="number", description="Client time offset"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function deleteAvailableTime(CreateAvailableTimesRequest $request)
    {
        try {
            $data = $this->userService->deleteAvailableTime($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/photo",
    *   summary="Get User Photo",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getUserPhotos(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $params = $request->all();
        $params['type'] = Consts::USER_MEDIA_PHOTO;

        $data = $this->userService->getUserPhotos($params);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/photo/create",
    *   summary="Create New User Photo",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="url", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="type", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function createUserPhoto(UserPhotoRequest $request)
    {
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['type'] = Consts::USER_MEDIA_PHOTO;

            $data = $this->userService->createUserPhoto($params);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/user/photo/delete",
    *   summary="Create New User Photo",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function deleteUserPhoto(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'id' => "required|exists:user_photos,id,user_id,{$userId}"
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->deleteUserPhoto($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/reviews",
    *   summary="Get User Reviews",
    *   tags={"Users"},
    *   security={
    *   },
    *   @SWG\Parameter(name="user_id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getUserReviews(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $data = $this->userService->getUserReviews($request->user_id, $request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/my-reviews",
    *   summary="Get My Reviews",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getMyReviews(Request $request)
    {
        $data = $this->userService->getMyReviews($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/followings",
    *   summary="Get My Follows",
    *   tags={"Users"},
    *   security={
    *   },
    *   @SWG\Parameter(name="user_id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getMyFollows(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $data = $this->userService->getMyFollowings($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/followers",
    *   summary="Get Followers",
    *   tags={"Users"},
    *   security={
    *   },
    *   @SWG\Parameter(name="user_id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getUserFollowers(Request $request)
    {
        $request->validate([
            'user_id' => 'required'
        ]);

        $data = $this->userService->getUserFollowers($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Put(
    *   path="/user/follow",
    *   summary="Add or Remove Follows",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="user_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="is_following", in="formData", required=true, type="integer", enum={1,0}),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function addOrRemoveFollow(Request $request)
    {
        $request->validate([
            'user_id' => 'required|not_in:' . Auth::id()
        ]);

        try {
            $data = $this->userService->addOrRemoveFollow($request->user_id, $request->is_following ? Consts::TRUE : Consts::FALSE);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/settings/update",
    *   summary="Update User Settings",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="message_email", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="favourite_email", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="marketing_email", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="bounty_email", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="session_email", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="public_chat", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="user_has_money_chat", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="visible_age", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="visible_gender", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="visible_following", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="online", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="cover", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function updateSettings(UserSettingRequest $request)
    {
        $request->auto_accept_booking = Consts::TRUE;
        $data = $this->userService->updateSettings(Auth::id(), $request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Put(
    *   path="/user/profile/update",
    *   summary="Update User Profile",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="avatar", in="formData", type="string"),
    *   @SWG\Parameter(name="description", in="formData", type="string"),
    *   @SWG\Parameter(name="dob", in="formData", type="string"),
    *   @SWG\Parameter(name="sex", in="formData", type="number"),
    *   @SWG\Parameter(
    *       name="languages[]",
    *       in="formData",
    *       type="array",
    *       items={
    *          {"type":"string"}
    *       }
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'languages' => 'array',
            'avatar'    => 'url',
            'dob'       => 'before:-13 years|date_format:d/m/Y'
        ]);

        $data = $this->userService->updateProfile(Auth::id(), $request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/change-email",
    *   summary="Send change email verification link from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="email", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changeEmailFromSetting(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:190|unique_email|regex:/^[\w+\.-]+@([\w-]+\.)+[\w-]{2,4}$/'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->changeEmailFromSetting($request->get('email'));
            DB::commit();
            $data = Utils::unsetFields($data, 'email_verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/change-email/without-verified-account",
    *   summary="Change email from setting without verified account",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="email", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changeEmailFromSettingWithoutVerifiedAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:190|unique_email|regex:/^[\w+\.-]+@([\w-]+\.)+[\w-]{2,4}$/'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->changeEmailFromSettingWithoutVerifiedAccount($request->get('email'));
            DB::commit();
            $data = Utils::unsetFields($data, 'email_verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/change-email/resend-link",
    *   summary="Resend change email verification link from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="email", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function resendLinkChangeEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->resendCodeChangeEmail($request->get('email'));
            DB::commit();
            $data = Utils::unsetFields($data, 'email_verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="change-email/cancel",
    *   summary="Cancel change email",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function cancelChangeEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->cancelChangeEmail();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/verify-change-email",
    *   summary="Verify change email",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function verifyChangeEmail(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $user = $this->userService->verifyChangeEmail($request->all());
            DB::commit();
            $user = Utils::unsetFields($user, 'email_verification_code');

            return $this->sendResponse($user);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/change-password",
    *   summary="Change password",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="old_password", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="new_password", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="repeat_password", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changePassword(ChangePasswordRequest $request)
    {
        $user = Auth::user();
        try {
            $user->password = bcrypt($request->input('new_password'));
            $user->save();
            Mail::queue(new ChangePasswordMail($user));
            return $this->sendResponse($user);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/social-network/create",
    *   summary="Create User Social Network Link",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="social_id", required=true, in="formData", type="string"),
    *   @SWG\Parameter(name="social_type", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function createSocialNetwork(Request $request)
    {
        $request->validate([
            'social_id' => 'required|string',
            'social_type' => 'required|string|social_type_valid'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->createSocialNetwork($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/social-network/update",
    *   summary="Updates Mutiple User Social Network Link",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="social_networks",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(
    *               type="array",
    *               property="social_networks",
    *               @SWG\Items(
    *                   @SWG\Property(property="social_id", type="string"),
    *                   @SWG\Property(property="social_type", type="string", enum={"discord","facebook","instagram","paypal","tiktok","twitch","twitter","youtube"})
    *               ),
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function updateSocialNetwork(Request $request)
    {
        $request->validate([
            '*.social_id'       => 'required|string',
            '*.social_type'     => 'required|string|social_type_valid'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->updateSocialNetwork($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/gamelancer-info",
    *   summary="Get Gamelancer Info",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getGamelancerInfo(Request $request)
    {
        $data = $this->userService->getGamelancerInfo(Auth::id());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/email-exists",
    *   summary="Email exists",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="email", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function checkEmailExists(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email|regex:/^[\w+\.-]+@([\w-]+\.)+[\w-]{2,4}$/'
        ]);

        return $this->sendResponse('ok');
    }

    /**
    * @SWG\Get(
    *   path="/username-valid",
    *   summary="Username Valid",
    *   tags={"Users"},
    *   security={
    *     {},
    *   },
    *   @SWG\Parameter(name="username", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUsernameValid(Request $request)
    {
        $request->validate([
            'username'  => 'required|min:3|max:20|unique_username',
        ]);

        return $this->sendResponse('ok');
    }

    /**
    * @SWG\Get(
    *   path="/username-exists",
    *   summary="Username Exists",
    *   tags={"Users"},
    *   security={
    *     {},
    *   },
    *   @SWG\Parameter(name="username", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUsernameExists(Request $request)
    {
        $request->validate([
            'username'  => 'required|exists_username',
        ]);

        return $this->sendResponse('ok');
    }

    /**
    * @SWG\Get(
    *   path="/phonenumber-exists",
    *   summary="Phone Number Exists",
    *   tags={"Users"},
    *   security={
    *     {},
    *   },
    *   @SWG\Parameter(name="phone_number", in="query", required=true, type="string"),
    *   @SWG\Parameter(name="phone_country_code", in="query", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkPhoneNumberExists(Request $request)
    {
        $request->validate([
            'phone_number'       => 'required|string|max:17|unique_phone_number',
            'phone_country_code' => 'required_with:phone_number|string|max:5|valid_phone_contry_code'
        ]);

        return $this->sendResponse('ok');
    }

    /**
    * @SWG\Get(
    *   path="/username-verified-account",
    *   summary="Username Verified Account",
    *   tags={"Users"},
    *   security={
    *     {},
    *   },
    *   @SWG\Parameter(name="username", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkUsernameVerifiedAccount(Request $request)
    {
        $request->validate([
            'username'  => 'required|exists_username|verified_account',
        ]);

        return $this->sendResponse('ok');
    }

     /**
    * @SWG\Delete(
    *   path="/user/social-network/delete",
    *   summary="Delete Social Network Link",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function deleteSocialNetwork(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $data = $this->userService->deleteSocialNetwork($request->id);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/invitation-code",
    *   summary="Get Invitation Code",
    *   tags={"Invitation Code"},
    *   security={
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getInvitationCode()
    {
        $data = $this->userService->getInvitationCode();
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/scheduler",
    *   summary="Get User Scheduler. The response time is UTC time.",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="user_id", required=true, in="query", type="string"),
    *   @SWG\Parameter(name="include_booked", required=false, in="query", type="string", description="true: both sesson booked, false: no contain session booked, defautl value: true"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function getUserScheduler(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $params = $request->all();
        $includeBookedSlot = ! empty($params['include_booked']);

        $data = $this->userService->getUserScheduler($request->user_id, $includeBookedSlot);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/report",
    *   summary="Report user",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="report_user_id", required=true, in="formData", type="string"),
    *   @SWG\Parameter(name="reason", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function report(ReportRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->report($request->all());
            $this->sendResponse($data);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/create-interests-games",
    *   summary="Create interests games",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *     name="interest-games",
    *     in="body",
    *     required=true,
    *     type="array",
    *     @SWG\Schema(
    *         @SWG\Items(
    *             @SWG\Property(property="game_id", type="string", description="Game id"),
    *             @SWG\Property(property="platform_id", type="string", description="platform id"),
    *             @SWG\Property(property="game_name", type="string", description="game name"),
    *             @SWG\Property(
    *               type="array",
    *               property="server_ids",
    *               @SWG\Items(type="number", description="Match Server ID"),
    *             ),
    *         )
    *     )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function createInterestsGames(InterestsGames $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->createInterestsGames($request->all());
            DB::commit();

            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/get-existed-interest-game",
    *   summary="Get existed interests games",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_id", required=true, in="query", type="string"),
    *   @SWG\Parameter(name="game_name", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getExistedInterestGame(Request $request)
    {
        $request->validate([
            'game_id'             => 'required|exists:games,id',
            'game_name'           => 'required|max:190'
        ]);

        try {
            $data = $this->userService->getExistedInterestGame($request->all());

            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/update-interests-games",
    *   summary="Update interests games",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *     name="interest-games",
    *     in="body",
    *     required=true,
    *     @SWG\Schema(
    *         @SWG\Property(property="id", type="string", description="Game id"),
    *         @SWG\Property(property="game_id", type="string", description="Game id"),
    *         @SWG\Property(property="platform_id", type="string", description="platform id"),
    *         @SWG\Property(property="game_name", type="string", description="game name"),
    *         @SWG\Property(
    *           type="array",
    *           property="server_ids",
    *           @SWG\Items(type="number", description="Match Server ID"),
    *         ),
    *     )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function updateInterestGame(Request $request)
    {
        $request->validate([
            'id'                  => 'required|exists:user_interests_games,id',
            'game_id'             => 'required|exists:games,id',
            'platform_id'         => 'required|exists:platforms,id',
            'game_name'           => 'required|max:190'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->preUpdateInterestGame($request->all());
            DB::commit();

            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    /**
    * @SWG\Delete(
    *   path="/user/delete-interests-game",
    *   summary="Delete interests game",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="user_interests_game_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function deleteInterestsGame(Request $request)
    {
        $request->validate([
            'user_interests_game_id' => 'required|exists:user_interests_games,id'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->deleteInterestsGame($request->user_interests_game_id);
            DB::commit();

            $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/get-interests-games",
    *   summary="Get interests games",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="user_id", in="query", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getInterestsGames(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $data = $this->userService->getInterestsGames($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/change-username-from-setting",
    *   summary="Send change username verification link from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="username", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changeUsernameFromSetting(Request $request)
    {
        $request->validate([
            'username' => 'required|unique_username|string|min:3|max:20|special_characters'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->changeUsernameFromSetting($request->get('username'));
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/resend-link-change-username",
    *   summary="Resend change username verification link from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="username", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function resendLinkChangeUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|unique_username|string|min:3|max:20|special_characters'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->resendlinkChangeUsername($request->username);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/cancel-change-username",
    *   summary="Cancel change username",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function cancelChangeUsername(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->cancelChangeUsername();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/verify-change-username",
    *   summary="Verify change username",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function verifyChangeUsername(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $user = $this->userService->verifyChangeUsername($request->verify_code);
            DB::commit();
            return $this->sendResponse($user);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/change-phone-number",
    *   summary="Send change phone number verification code from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="phone_country_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changePhoneNumberFromSetting(Request $request)
    {
        $request->validate([
            // 'phone_number'       => 'required|string|max:15|phone:phone_country_code|unique_phone_number',
            'phone_number'       => 'required|string|max:17|unique_phone_number',
            'phone_country_code' => 'required_with:phone_number|string|max:5|valid_phone_contry_code',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->changePhoneNumberFromSetting($request->phone_number, $request->phone_country_code);
            DB::commit();
            $data = Utils::unsetFields($data, 'verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/change-phone-number/without-verified-account",
    *   summary="Change phone number from setting without verified account",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="phone_country_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changePhoneNumberFromSettingWithoutVerifiedAccount(Request $request)
    {
        $request->validate([
            // 'phone_number'       => 'required|string|max:15|phone:phone_country_code|unique_phone_number',
            'phone_number'       => 'required|string|max:17|unique_phone_number',
            'phone_country_code' => 'required_with:phone_number|string|max:5',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->changePhoneNumberFromSettingWithoutVerifiedAccount($request->phone_number, $request->phone_country_code);
            DB::commit();
            $data = Utils::unsetFields($data, 'verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/change-phone-number/resend-link",
    *   summary="Resend change phone number verification code from setting",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function resendCodeChangePhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15'
        ]);
        DB::beginTransaction();
        try {
            $data = $this->userService->resendCodeChangePhoneNumber($request->phone_number);
            DB::commit();
            $data = Utils::unsetFields($data, 'verification_code');

            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/change-phone-number/cancel",
    *   summary="Cancel change phone number",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function cancelChangePhoneNumber(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->cancelChangePhoneNumber();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/verify-change-phone-number",
    *   summary="Verify change phone number",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function verifyChangePhoneNumber(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $user = $this->userService->verifyChangePhoneNumber($request->verify_code);
            DB::commit();
            $user = Utils::unsetFields($user, 'phone_verify_code');

            return $this->sendResponse($user);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/send-otp-code",
    *   summary="Send OTP code to phone or email, prioty is email",
    *   tags={"Users"},
    *   security={
    *    {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function sendOtpCode(Request $request)
    {
        $user = $this->userService->sendOtpCode();
        return $this->sendResponse('ok');
    }

    /**
    * @SWG\Post(
    *   path="/confirm-otp-code",
    *   summary="Send confirm code to unlock",
    *   tags={"Users"},
    *   security={
    *    {"passport": {}},
    *   },
    *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function confirmOtpCode(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        $confirmationCode = $request->verify_code;
        $result = $this->userService->confirmOtpCode($confirmationCode);
        return $this->sendResponse($result);
    }

    /**
    * @SWG\Post(
    *   path="/save-phone-for-user",
    *   summary="Save phone for user miss phone number",
    *   tags={"Users"},
    *   security={},
    *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="phone_country_code", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function savePhoneForUser(Request $request)
    {
        $request->validate([
            'phone_number'       => 'required|string|max:17|unique_phone_number',
            'phone_country_code' => 'required_with:phone_number|string|max:5',
        ]);
        DB::beginTransaction();
        try {
            $user = $this->userService->savePhoneForUser($request->phone_number, $request->phone_country_code);
            DB::commit();
            return $this->sendResponse($user);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/get-invitation-code",
    *   summary="Get invitation code",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getInvitationCodeForVip(Request $request)
    {
        $data = $this->userService->getInvitationCodeForVip($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/check-password-valid",
    *   summary="Check password valid",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="password", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkPasswordValid(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);
        DB::beginTransaction();
        try {
            $user = $this->userService->checkPasswordValid($request->password);
            DB::commit();
            return $this->sendResponse('ok');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/user/taskings",
    *   summary="Get User Tasks And Rewards",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="type", in="formData", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUserTasks(Request $request)
    {
        $type = empty($request->type) ? null : $request->type;

        $data = $this->userService->getUserTaskings($type);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/intro-tasks/collect",
    *   summary="Collecting Step Intro Tasks",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="step", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function collectStepIntroTask(Request $request)
    {
        $totalSteps = Consts::TOTAL_INTRO_STEPS;

        $request->validate([
            'step' => "required|numeric|gte:1|lte:{$totalSteps}"
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->collectStepIntroTask($request->step);
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/taskings/collect",
    *   summary="Collecting User Tasking",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="tasking_id", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function collectUserTasking(Request $request)
    {
        $request->validate([
            'tasking_id' => 'required|exists:taskings,id'
        ]);

        DB::beginTransaction();
        try {
            $this->userService->collectUserTasking($request->tasking_id);
            DB::commit();
            return $this->sendResponse('ok');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/taskings/claim",
    *   summary="Claim Tasking",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="type", in="formData", required=true, type="string", enum={"intro", "daily"}),
    *   @SWG\Parameter(name="level", in="formData", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function claimTasking(Request $request)
    {
        $request->validate([
            'type'  => ['required', Rule::in(Consts::TASKING_TYPE_INTRO, Consts::TASKING_TYPE_DAILY, Consts::TASKING_TYPE_DAILY_CHECKIN)],
            'level' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $this->userService->claimTasking($request->type, $request->level);
            DB::commit();
            return $this->sendResponse('ok');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/daily-checkin/collect",
    *   summary="Claim Tasking",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="id", in="formData", required=true, type="integer", description="checkin id"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function collectDailyCheckin(Request $request)
    {
        $request->validate([
            'id'  => 'required|exists:daily_checkins,id'
        ]);

        DB::beginTransaction();
        try {
            $checkinId = $request->id;
            $this->userService->collectDailyCheckin($checkinId);
            DB::commit();
            return $this->sendResponse('ok');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/reset-ranking",
    *   summary="Reset User Raking",
    *   tags={"Cheat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function resetUserRanking(Request $request)
    {
        DB::beginTransaction();
        try {
            $this->userService->resetUserRanking();
            DB::commit();
            return $this->sendResponse('ok');
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

        /**
    * @SWG\Get(
    *   path="/get-unlock-security-type",
    *   summary="Get Unlock Security Type",
    *   tags={"Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUnlockSecurityType(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->getUnlockSecurityType();
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
