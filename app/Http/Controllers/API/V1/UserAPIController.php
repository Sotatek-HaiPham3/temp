<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\Reports\InvalidCodeException;
use App\Exceptions\Reports\InvalidCredentialException;
use App\Http\Services\CommunityService;
use App\Http\Services\VoiceService;
use App\Models\SocialUser;
use App\PhoneUtils;
use App\Utils\BearerToken;
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
     *   path="/v1/user/profile",
     *   summary="Get User Profile",
     *   tags={"V1.Users"},
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
     *   path="/v1/invitation-code/is-valid",
     *   summary="Validate Invitation Code",
     *   tags={"V1.Users"},
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
     *   path="/v1/user/balance",
     *   summary="Get User Balance",
     *   tags={"V1.Users"},
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
     *   path="/v1/become-gamelancer",
     *   summary="Submit Gamelancer Information",
     *   tags={"V1.Users"},
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
     *   path="/v1/user-info/{username}",
     *   summary="Get User Information by Username",
     *   tags={"V1.Users"},
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
     *   path="/v1/user/available-times",
     *   summary="Get User Available Times",
     *   tags={"V1.Users"},
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
     *   path="/v1/user/available-times/add",
     *   summary="Add Available Times",
     *   tags={"V1.Users"},
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
    *   path="/v1/user/available-times/delete",
    *   summary="Delete Available Times Item",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/photo",
    *   summary="Get User Photo",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/photo/create",
    *   summary="Create New User Photo",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/photo/delete",
    *   summary="Create New User Photo",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/reviews",
    *   summary="Get User Reviews",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/my-reviews",
    *   summary="Get My Reviews",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/followings",
    *   summary="Get My Follows",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/followers",
    *   summary="Get Followers",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/follow",
    *   summary="Add or Remove Follows",
    *   tags={"V1.Users"},
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
            'user_id' => 'required|exists:users,id|not_in:' . Auth::id(),
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->addOrRemoveFollow($request->user_id, $request->is_following ? Consts::TRUE : Consts::FALSE);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/user/settings/update",
    *   summary="Update User Settings",
    *   tags={"V1.Users"},
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
    *   @SWG\Parameter(name="follower_notification", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="room_invite_notification", in="formData", type="integer", enum={1,0}),
    *   @SWG\Parameter(name="room_start_notification", in="formData", type="integer", enum={1,0}),
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
     * @SWG\Get(
     *   path="/v1/user/settings",
     *   summary="get User Settings",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserSettings()
    {
        $data = $this->userService->getUserSettings(Auth::id());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Put(
    *   path="/v1/user/profile/update",
    *   summary="Update User Profile",
    *   tags={"V1.Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="avatar", in="formData", type="string"),
    *   @SWG\Parameter(name="description", in="formData", type="string"),
    *   @SWG\Parameter(name="dob", in="formData", type="string"),
    *   @SWG\Parameter(name="sex", in="formData", type="string"),
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
        $userId = Auth::id();
        $request->validate([
            'username'  => "unique:users,username,{$userId}|string|min:3|max:50|special_characters",
            'languages' => 'array',
            'avatar'    => ['nullable', 'url'],
            'dob'       => 'before:-13 years|date_format:d/m/Y',
            'sex'       => ['nullable', Rule::in([Consts::GENDER_FEMALE, Consts::GENDER_MALE, Consts::GENDER_NON_BINARY, Consts::GENDER_NOT_SAY])],
            'description' => 'max:500',
            'email' => 'max:190'
        ]);

        $data = $this->userService->updateProfile($userId, $request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/v1/verify-change-email",
    *   summary="Verify change email",
    *   tags={"V1.Users"},
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
    *   path="/v1/security/change-password",
    *   summary="Add or Change password (new)",
    *   tags={"V1.Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="old_password", in="formData", required=false, type="string"),
    *   @SWG\Parameter(name="password", in="formData", required=true, type="string"),
    *   @SWG\Parameter(name="confirmation_password", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function changePassword(ChangePasswordRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->changePassword($request->password);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/social-network/create",
    *   summary="Create User Social Network Link",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/social-network/update",
    *   summary="Updates Mutiple User Social Network Link",
    *   tags={"V1.Users"},
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
            '*.social_id'       => 'required',
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
    *   path="/v1/user/gamelancer-info",
    *   summary="Get Gamelancer Info",
    *   tags={"V1.Users"},
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
    *   path="/v1/email-exists",
    *   summary="Email exists",
    *   tags={"V1.Users"},
    *   security={},
    *   @SWG\Parameter(name="email", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function checkEmailExists(Request $request)
    {
        $request->validate(
            ['email' => 'required|email|special_characters_email']
        );

        $data = $this->userService->checkEmailExists($request->email);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/username-valid",
    *   summary="Username Valid",
    *   tags={"V1.Users"},
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
        $userId = $request->user_id;
        $request->validate([
            'username'  => "required|min:3|max:50|special_characters|unique:users,username,{$userId}",
        ]);

        return $this->sendResponse([]);
    }

    /**
     * @SWG\Get(
     *   path="/v1/phonenumber-valid",
     *   summary="Phone number Valid",
     *   tags={"V1.Users"},
     *   security={
     *     {},
     *   },
     *   @SWG\Parameter(name="phone_number", required=true, in="query", type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkPhonenumberValid(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|valid_phone_number'
        ]);
        return $this->sendResponse([]);
    }

    /**
    * @SWG\Get(
    *   path="/v1/username-exists",
    *   summary="Username Exists",
    *   tags={"V1.Users"},
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
            'username'  => 'required|unique:users,username',
        ]);

        return $this->sendResponse([]);
    }

    /**
    * @SWG\Get(
    *   path="/v1/phonenumber-exists",
    *   summary="Phone Number Exists",
    *   tags={"V1.Users"},
    *   security={
    *     {},
    *   },
    *   @SWG\Parameter(name="phone_number", in="query", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function checkPhoneNumberExists(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|valid_phone_number'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        $data = $this->userService->checkPhoneNumberExists($request->all());

        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/username-verified-account",
    *   summary="Username Verified Account",
    *   tags={"V1.Users"},
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

        return $this->sendResponse([]);
    }

     /**
    * @SWG\Delete(
    *   path="/v1/user/social-network/delete",
    *   summary="Delete Social Network Link",
    *   tags={"V1.Users"},
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
    *   path="/v1/invitation-code",
    *   summary="Get Invitation Code",
    *   tags={"V1.Invitation Code"},
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
    *   path="/v1/user/scheduler",
    *   summary="Get User Scheduler. The response time is UTC time.",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/report",
    *   summary="Report user",
    *   tags={"V1.Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="report_user_id", required=true, in="formData", type="integer"),
    *   @SWG\Parameter(name="reason_id", required=true, in="formData", type="integer"),
    *   @SWG\Parameter(name="details", required=false, in="formData", type="string"),
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
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/create-interests-games",
    *   summary="Create interests games",
    *   tags={"V1.Users"},
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

            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/get-existed-interest-game",
    *   summary="Get existed interests games",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/update-interests-games",
    *   summary="Update interests games",
    *   tags={"V1.Users"},
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

            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }


    /**
    * @SWG\Delete(
    *   path="/v1/user/delete-interests-game",
    *   summary="Delete interests game",
    *   tags={"V1.Users"},
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

            $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/get-interests-games",
    *   summary="Get interests games",
    *   tags={"V1.Users"},
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
    *   path="/v1/verify-change-username",
    *   summary="Verify change username",
    *   tags={"V1.Users"},
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
    *   path="/v1/verify-change-phone-number",
    *   summary="Verify change phone number",
    *   tags={"V1.Users"},
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
     *   path="/v1/send-otp-code",
     *   summary="Send OTP code to phone or email, prioty is email",
     *   tags={"V1.Users"},
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
        $this->userService->sendOtpCode();
        return $this->sendResponse([]);
    }

    /**
    * @SWG\Post(
    *   path="/v1/confirm-otp-code",
    *   summary="Send confirm code to unlock",
    *   tags={"V1.Users"},
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
        $result = $this->userService->confirmOtpCode($confirmationCode, false);
        return $this->sendResponse($result);
    }

    /**
    * @SWG\Post(
    *   path="/v1/save-phone-for-user",
    *   summary="Save phone for user miss phone number",
    *   tags={"V1.Users"},
    *   security={},
    *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    */
    public function savePhoneForUser(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|valid_phone_number|unique_phone_number'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        DB::beginTransaction();
        try {
            $user = $this->userService->savePhoneForUser($request->phone_number);
            DB::commit();
            return $this->sendResponse($user);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/get-invitation-code",
    *   summary="Get invitation code",
    *   tags={"V1.Users"},
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
    *   path="/v1/check-password-valid",
    *   summary="Check password valid",
    *   tags={"V1.Users"},
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
            return $this->sendResponse([]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/taskings",
    *   summary="Get User Tasks And Rewards",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/intro-tasks/collect",
    *   summary="Collecting Step Intro Tasks",
    *   tags={"V1.Users"},
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
    *   path="/v1/user/taskings/collect",
    *   summary="Collecting User Tasking",
    *   tags={"V1.Users"},
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
            return $this->sendResponse([]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/taskings/claim",
    *   summary="Claim Tasking",
    *   tags={"V1.Users"},
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
            return $this->sendResponse([]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/daily-checkin/collect",
    *   summary="Claim Tasking",
    *   tags={"V1.Users"},
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
            return $this->sendResponse([]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/reset-ranking",
    *   summary="Reset User Raking",
    *   tags={"V1.Cheat"},
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
            return $this->sendResponse([]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

        /**
    * @SWG\Get(
    *   path="/v1/get-unlock-security-type",
    *   summary="Get Unlock Security Type",
    *   tags={"V1.Users"},
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

    /**
    * @SWG\Get(
    *   path="/v1/user/list-friend",
    *   summary="Get List Friend",
    *   tags={"V1.Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getListFriend(Request $request)
    {
        $data = $this->userService->getListFriend();
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/v1/users-existed",
    *   summary="Get Users Existed",
    *   tags={"V1.Users"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *         name="data[]",
    *         in="query",
    *         required=true,
    *         type="array",
    *         collectionFormat="multi",
    *         @SWG\Items(type="string")
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid."),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUsersExisted(Request $request)
    {
        $request->validate([
            'data'      => 'required|array',
            'data.*'    => 'required'
        ]);

        $params = $request->all();
        $data = $this->userService->getUsersExisted($params);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/v1/user/block-list",
     *   summary="Get My block list",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="limit", in="query", required=false, type="integer"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getMyBlockList(Request $request)
    {
        $data = $this->userService->getMyBlockList($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Put(
     *   path="/v1/user/block",
     *   summary="Add or Remove block",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="user_id", in="formData", required=true, type="integer"),
     *   @SWG\Parameter(name="is_blocked", in="formData", required=true, type="integer", enum={1,0}),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function addOrRemoveBlock(Request $request)
    {
        $request->validate([
            'user_id' => 'required|not_in:' . Auth::id(),
            'is_blocked' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->addOrRemoveBlock($request->user_id, $request->is_blocked ? Consts::TRUE : Consts::FALSE);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/user/recent-games",
     *   summary="Get user recent joined games",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="user_id", in="query", required=true, type="integer"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getRecentRoomGames(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
        ]);

        try {
            $data = $this->userService->getRecentRoomGames($request->user_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/change-email",
     *   summary="Change Email",
     *   tags={"V1.Users"},
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
    public function changeEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->changeEmail($request->email);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/change-phone",
     *   summary="Change Phone",
     *   tags={"V1.Users"},
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
    public function changePhone(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|valid_phone_number|unique_phone_number'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        DB::beginTransaction();
        try {
            $data = $this->userService->changePhone($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/verify-email",
     *   summary="Verify Email",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="email", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="code", in="formData", required=true, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function verifyEmail(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'email' => "required|string|email|max:255|unique:users,email,{$userId}",
            'code' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->verifyEmail($request->all(), $request->ip());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/verify-phone",
     *   summary="Verify Phone",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="phone_number", in="formData", required=true, type="string"),
     *   @SWG\Parameter(name="code", in="formData", required=true, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function verifyPhone(Request $request)
    {
        $userId = Auth::id();
        $request->validate([
            'phone_number' => "required|valid_phone_number|unique_phone_number:{$userId}",
            'code' => 'required'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        DB::beginTransaction();
        try {
            $data = $this->userService->verifyPhone($request->all(), $request->ip());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/send-email-verification-code",
     *   summary="Send Email Verification Code",
     *   tags={"V1.Users"},
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
    public function sendEmailVerificationCode(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'email' => $user->email_verified ? 'required|string|email|max:255|unique:users,email' : ''
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->sendEmailVerificationCode($user, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/send-phone-verification-code",
     *   summary="Send Phone Verification Code",
     *   tags={"V1.Users"},
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
    public function sendPhoneVerificationCode(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'phone_number' => $user->phone_verified ? 'required|valid_phone_number|unique_phone_number' : ''
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        DB::beginTransaction();
        try {
            $data = $this->userService->sendPhoneVerificationCode($user, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/cancel-changing-email",
     *   summary="Cancel Changing Email",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function cancelChangingEmail()
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->cancelChangingEmail();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/security/cancel-changing-phone",
     *   summary="Cancel Changing Phone",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function cancelChangingPhone()
    {
        DB::beginTransaction();
        try {
            $data = $this->userService->cancelChangingPhone();
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/security/change-username",
    *   summary="Change user username",
    *   tags={"V1.Users"},
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
    public function changeUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username|string|min:3|max:50|special_characters'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->changeUsername($request->get('username'));
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/user/playing-friends",
     *   summary="Get playing friends",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="limit", in="query", required=false, type="integer"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function getPlayingFriends(Request $request)
    {
        $data = $this->userService->getPlayingFriends($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/v1/user/suggest-friends",
     *   summary="Get suggest friends",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="limit", in="query", required=false, type="integer"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
    */
    public function getSuggestFriends(Request $request)
    {
        $data = $this->userService->getSuggestFriends($request->all());
        return $this->sendResponse($data);
    }
    /**
     * @SWG\Post(
     *   path="/v1/user/delete-account",
     *   summary="Excute delete account",
     *   tags={"V1.Users"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="verify_code", in="formData", required=false, type="integer"),
     *   @SWG\Parameter(name="token", in="formData", required=false, type="string"),
     *   @SWG\Parameter(name="provider", in="formData", required=false, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();
            $isUserSocial = SocialUser::where('user_id', $user->id)->exists();
            $isCommonVerify = ($user->email_verified || $user->phone_verified) ? true : false;
            $isSocialVerify = !$isCommonVerify && $isUserSocial;
            $request->validate([
                'verify_code' => $isCommonVerify ? 'required' : '',
                'token' => $isSocialVerify ? 'required' : '',
                'provider' => $isSocialVerify ? 'required|provider_valid' : '',
            ]);
            $confirmationCode = array_get($request, 'verify_code');
            if ($confirmationCode) {
                $checkCode = $this->userService->confirmOtpCode($confirmationCode, true);
                if (!$checkCode) {
                    throw new InvalidCodeException();
                }
            }

            $token = array_get($request, 'token');
            if ($token) {
                $userId = Auth::id();
                $providerUser = $this->userService->getProviderUser($request);
                $socialUser = SocialUser::where('provider', $request->provider)->where('provider_id', $providerUser->id)->where('user_id', $userId)->first();
                if (!$socialUser) {
                    throw new InvalidCredentialException(__('exceptions.invalid_token_social'));
                }
            }

            $data = $this->userService->deleteUser();
            $voiceService = new VoiceService();
            $voiceService->leaveAnyRoom();

            // remove all access token
            $accessTokenIds = DB::table('oauth_access_tokens')->where('user_id', $user->id)->get()->pluck('id')->toArray();
            DB::table('oauth_access_tokens')->whereIn('id', $accessTokenIds)->delete();
            DB::table('oauth_refresh_tokens')->whereIn('access_token_id', $accessTokenIds)->delete();

            $communityService = new CommunityService();
            $communityService->leaveAnyCommunity(Auth::id());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

}
