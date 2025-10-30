<?php

namespace App\Http\Controllers\API\V2;

use App\Events\UserOnline;
use App\Http\Controllers\AppBaseController as AppBase;
use App\Http\Services\UserService;
use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mail;

class UserAPIController extends AppBase
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @SWG\Get(
     *   path="/v2/user/profile",
     *   summary="Get User Profile",
     *   tags={"V2.Users"},
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
     * @SWG\Post(
     *   path="/v2/security/send-email-otp-code",
     *   summary="Send OTP code to email",
     *   tags={"V2.Users"},
     *   security={
     *    {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendEmailOtpCode()
    {
        $this->userService->sendEmailOtpCode();
        return $this->sendResponse([]);
    }

    /**
     * @SWG\Post(
     *   path="/v2/security/send-phone-otp-code",
     *   summary="Send OTP code to phone",
     *   tags={"V2.Users"},
     *   security={
     *    {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendPhoneOtpCode()
    {
        $this->userService->sendPhoneOtpCode();
        return $this->sendResponse([]);
    }

    /**
     * @SWG\Post(
     *   path="/v2/security/confirm-email-otp-code",
     *   summary="Confirm email otp code",
     *   tags={"V2.Users"},
     *   security={
     *    {"passport": {}},
     *   },
     *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function confirmEmailOtpCode(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        $confirmationCode = $request->verify_code;
        $result = $this->userService->confirmEmailOtpCode($confirmationCode, true);
        return $this->sendResponse($result);
    }

    /**
     * @SWG\Post(
     *   path="/v2/security/confirm-phone-otp-code",
     *   summary="Confirm phone otp code",
     *   tags={"V2.Users"},
     *   security={
     *    {"passport": {}},
     *   },
     *   @SWG\Parameter(name="verify_code", in="formData", required=true, type="string"),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function confirmPhoneOtpCode(Request $request)
    {
        $request->validate([
            'verify_code' => 'required'
        ]);
        $confirmationCode = $request->verify_code;
        $result = $this->userService->confirmPhoneOtpCode($confirmationCode, true);
        return $this->sendResponse($result);
    }
}
