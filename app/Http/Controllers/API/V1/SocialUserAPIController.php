<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Socialite;
use App;
use App\Consts;
use App\PhoneUtils;
use App\Utils\OtpUtils;
use App\Models\User;
use App\Models\SocialUser;
use App\Http\Requests\SocialRequest;
use GuzzleHttp\Exception\ClientException;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Socialite\Two\InvalidStateException;
use App\Exceptions\Reports\InvalidCodeException;
use App\Exceptions\Reports\InvalidCredentialException;
use App\Exceptions\Reports\UserExistedException;
use App\Http\Services\MasterdataService;
use App\Http\Services\UserService;
use App\Traits\RegisterTrait;
use App\Traits\GenerateBearerTokenTrait;

class SocialUserAPIController extends AccessTokenController
{
    use HandlesOAuthErrors, RegisterTrait, GenerateBearerTokenTrait;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @SWG\Post(
     *   path="/v1/social/auth-token",
     *   summary="Login with Google, Facebook, Discord, Apple, Tiktok",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="provider",
     *       in="formData",
     *       enum={"google", "facebook", "discord", "apple", "snapchat", "tiktok"},
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="token",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function authToken(Request $request)
    {
        $request->validate([
            'provider' => 'required|provider_valid',
            'token' => 'required'
        ]);

        try {
            $providerUser = $this->userService->getProviderUser($request);
            $socialUser = SocialUser::where('provider', $request->provider)->where('provider_id', $providerUser->id)->first();

            if ($socialUser) {
               $user = User::find($socialUser->user_id);
               return $this->generateBearerToken($user);
            }

            $extraData = $this->buildExtraDataIfNeed($providerUser);

            return $this->sendResponse(array_merge($extraData, [
                'new_user' => Consts::TRUE,
                'email' => $providerUser->email,
                'username' => $providerUser->nickname,
                'name' => $providerUser->name,
                'avatar' => $providerUser->avatar
            ]));
        } catch (InvalidStateException $e) {
            throw new InvalidCredentialException(__('exceptions.invalid_token'));
        } catch (ClientException $e) {
            throw new InvalidCredentialException(__('exceptions.invalid_token'));
        }
    }

    protected function buildExtraDataIfNeed($providerUser)
    {
        $extraData = [];
        if (isset($providerUser->token)) {
            $extraData['token'] = $providerUser->token; // for tiktok and snapchat
        }

        if (isset($providerUser->open_id)) {
            $extraData['open_id'] = $providerUser->open_id; // for tiktok
        }

        return $extraData;
    }

    protected function sendResponse($data) {
        $res = [
            'success' => true,
            'dataVersion' => MasterdataService::getDataVersion(),
            'data' => $data,
        ];
        return response()->json($res);
    }


    /**
     * @SWG\Post(
     *   path="/v1/social/register",
     *   summary="Login with Google, Facebook, Discord, Apple, Tiktok",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="username",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="dob",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="languages[]",
     *       in="formData",
     *       required=true,
     *       type="array",
     *       items={
     *          {"type":"string"}
     *       }
     *   ),
     *  @SWG\Parameter(
     *       name="agree_term",
     *       in="formData",
     *       required=true,
     *       type="number"
     *   ),
     *  @SWG\Parameter(
     *       name="provider",
     *       in="formData",
     *       enum={"google", "facebook", "discord", "apple", "snapchat", "tiktok"},
     *       required=true,
     *       type="string"
     *   ),
        @SWG\Parameter(
     *       name="token",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function register(SocialRequest $request)
    {
        try {
            $providerUser = $this->userService->getProviderUser($request);

            $socialUser = SocialUser::where('provider', $request->provider)
                ->where('provider_id', $providerUser->id)
                ->first();

            if ($socialUser) {
                throw new UserExistedException();
            }

            $options = [
                'provider' => $request->provider,
                'provider_user' => $providerUser
            ];

            $user = $this->doRegister($request->all(), $options);

            return $this->sendResponse([]);
        } catch(ClientException $e) {
            throw new InvalidCredentialException("invalid_{$request->provider}_token");
        }
    }

    /**
     * @SWG\Get(
     *   path="/v1/social/authorization/users",
     *   summary="Get all user info with email or phone number",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="email",
     *       in="query",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="query",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getAuthorizationUsers(Request $request)
    {
        $request->validate([
            'phone_number' => $request->email ? '' : 'required',
            'email'  => $request->phone_number ? '' : 'required'
        ]);
        if ($request->phone_number) {
            $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);
        }

        $data = $this->userService->getAuthorizationUsers($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v1/social/authorization/send-email-code",
     *   summary="Send code for attaching social account via email",
     *   tags={"Authentication"},
     *   security={
     *   },
     *   @SWG\Parameter(
     *       name="user_id",
     *       in="formData",
     *       required=true,
     *       type="integer"
     *   ),
     *   @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendEmailAuthorizationCode(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'email'  => 'required|exists:users,email'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->userService->sendEmailAuthorizationCode($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/social/authorization/send-phone-number-code",
     *   summary="Send code for attaching social account via phone number",
     *   tags={"Authentication"},
     *   security={
     *   },
     *   @SWG\Parameter(
     *       name="user_id",
     *       in="formData",
     *       required=true,
     *       type="integer"
     *   ),
     *   @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendPhoneAuthorizationCode(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone_number' => 'required|exists_phone_number'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        DB::beginTransaction();
        try {
            $data = $this->userService->sendPhoneAuthorizationCode($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/social/authorization/confirm-code",
     *   summary="Login with social - confirm code and attach social account to profile",
     *   tags={"Authentication"},
     *   security={
     *   },
     *   @SWG\Parameter(
     *       name="user_id",
     *       in="formData",
     *       required=true,
     *       type="integer"
     *   ),
     *   @SWG\Parameter(
     *       name="code",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="provider",
     *       in="formData",
     *       enum={"google", "facebook", "discord", "apple", "snapchat", "tiktok"},
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="token",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Parameter(
     *       name="open_id",
     *       description="The open id field is required when provider is tiktok",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function confirmAuthorizationCode(Request $request) {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'code' => 'required',
            'provider' => 'required',
            'token' => 'required'
        ]);

        $providerUser = $this->userService->getProviderUser($request);

        DB::beginTransaction();
        try {
            $data = $this->userService->attachSocialAccount($request->all(), $providerUser);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @param Request $request
     * @description handle Sign in with Apple for Android
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleAppleCallback(Request $request)
    {
        $request = $request->all();
        $token = array_get($request, 'id_token');
        $code = array_get($request, 'code');
        return Redirect::to(env('WEB_APP_URL', 'https://connect.gamelancer.com') . '/social-checking?token=' . $token . '&code=' . $code);
    }
}
