<?php

namespace App\Http\Controllers\API\V1;

use App\Exceptions\Reports\ResetPasswordUserException;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\ResetPasswordService;
use App\PhoneUtils;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResetPasswordController extends AppBaseController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    protected $resetPasswordService;

    /**
     * Create a new controller instance.
     * @param ResetPasswordService $resetPasswordService
     * @return void
     */
    public function __construct(ResetPasswordService $resetPasswordService)
    {
        $this->middleware('guest');
        $this->resetPasswordService = $resetPasswordService;
    }

    /**
     * @SWG\Post(
     *   path="/v1/reset-password/send-code",
     *   summary="Reset Password",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *   @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=false,
     *       type="string"
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
    public function sendResetPasswordCode(Request $request) {
        $request->validate([
            'phone_number' => $request->email ? '' : 'required|exists_phone_number',
            'email'  => $request->phone_number ? '' : 'required|exists:users,email'
        ]);
        if ($request->phone_number) {
            $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);
        }

        DB::beginTransaction();
        try {
            $data = $this->resetPasswordService->sendResetPasswordCode($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/reset-password/execute",
     *   summary="Execute reset password by code",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="password",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="code",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="password_confirmation",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function executeResetPassword(Request $request)
    {
        $request->validate([
            'phone_number' => $request->email ? '' : 'required|exists_phone_number',
            'email'  => $request->phone_number ? '' : 'required|exists:users,email',
            'code' => 'required',
            'password' => 'required|string|min:6|max:72|regex:/(?=.*[A-Z]).+$/|confirmed|password_white_space'
        ]);
        if ($request->phone_number) {
            $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);
        }

        DB::beginTransaction();
        try {
            $data = $this->resetPasswordService->executeResetPassword($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/reset-password/confirm-code",
     *   summary="Check valid code when reset Password by phone number",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="query",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="email",
     *       in="query",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="code",
     *       in="query",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function confirmResetPwCode(Request $request)
    {
        $request->validate([
            'phone_number' => $request->email ? '' : 'required',
            'email'  => $request->phone_number ? '' : 'required|exists:users,email',
            'code'  => 'required'
        ]);
        if ($request->phone_number) {
            $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);
        }

        try {
            $data = $this->resetPasswordService->confirmResetPwCode($request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
