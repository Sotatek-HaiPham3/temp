<?php

namespace App\Http\Controllers\API\V1;

use App\Consts;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Http\Controllers\AppBaseController;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Session;
use Mail;
use DB;
use App\Models\UserSecuritySetting;
use App\Mails\VerificationMailQueue;
use Log;
use App\Http\Services\UserService;
use App\Http\Services\SystemNotification;
use App\Utils;
use App\PhoneUtils;
use Jenssegers\Agent\Facades\Agent;
use Carbon\Carbon;
use App\Events\UserUpdated;
use App\Traits\RegisterTrait;
use App\Exceptions\Reports\InvalidCodeVerificationException;
use App\Exceptions\Reports\EmailVerifiedException;
use App\Exceptions\Reports\PhoneNumberVerifiedException;
use App\Exceptions\Reports\ChangePhoneNumberException;
use Illuminate\Validation\ValidationException;
use Propaganistas\LaravelPhone\PhoneNumber;
use App\Jobs\AddKlaviyoMailList;
use App\Jobs\SendSmsNotificationJob;
use App\Traits\GenerateBearerTokenTrait;

class RegisterAPIController extends AppBaseController
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers, RegisterTrait, GenerateBearerTokenTrait;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    private $userService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
        $this->userService = new UserService();
    }

    /**
     * @SWG\Post(
     *   path="/v1/register",
     *   summary="Register",
     *   tags={"V1.Authentication"},
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
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="password",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="password_confirmation",
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
     *       name="validate_code",
     *       in="formData",
     *       required=false,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->doRegister($request->all());
            $token = $this->generateBearerToken($user);

            return response()->json($token);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/send-verification-code-for-email",
     *   summary="Send verification code active account for email",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendVerificationCodeForEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255|exists:users,email'
        ]);

        DB::beginTransaction();
        try {
            $user = $user = User::where('email', $request->email)->first();

            if ($user->email_verified) {
                throw new EmailVerifiedException();
            }

            $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
            $user->email_verification_code = $confirmationCode;
            $user->email_verification_code_created_at = Carbon::now();
            $user->save();

            Mail::queue(new VerificationMailQueue($user, Consts::DEFAULT_LOCALE));

            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/verify-email",
     *   summary="Verify email",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="email",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="verify_code",
     *       in="formData",
     *       required=true,
     *       type="number"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function verifyEmail(Request $request)
    {
        Validator::make($request->all(), [
            'email'                => 'required|string|email|max:255',
            'verify_code'          => 'required|digits:6'
        ])->validate();

        $params = [
            'email' => $request->email,
            'email_verification_code' => $request->verify_code,
        ];

        $user = User::where($params)->first();
        $this->validateForVerify($user);

        DB::beginTransaction();
        try {
            $user->email = $request->email;
            $user->email_verified = Consts::TRUE;
            $user->email_verification_code = null;
            $user->email_verification_code_created_at = null;
            $user->save();

            if ($user->canActiveAndCreateBalanceForUser()) {
                $this->activeAndCreateBalanceForUser($user, $request->ip());
            }

            DB::commit();
            event(new UserUpdated($user->id));
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/verify-phone-number",
     *   summary="Verify phone number",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="verify_code",
     *       in="formData",
     *       required=true,
     *       type="number"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function verifyPhoneNumber(Request $request)
    {
        Validator::make($request->all(), [
            'phone_number'         => 'required|string|max:17',
            'verify_code'          => 'required|digits:6'
        ])->validate();

        $params = [
            'phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number),
            'phone_verify_code' => $request->verify_code
        ];

        $user = User::where($params)->first();
        $this->validateForVerify($user, Consts::TRUE);

        DB::beginTransaction();
        try {
            $user->phone_number = $request->phone_number;
            $user->phone_verified = Consts::TRUE;
            $user->phone_verify_code = null;
            $user->phone_verify_created_at = null;
            $user->save();

            if ($user->canActiveAndCreateBalanceForUser()) {
                $this->activeAndCreateBalanceForUser($user, $request->ip());
            }

            DB::commit();
            event(new UserUpdated($user->id));
            return $this->sendResponse([]);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function validateForVerify($user, $isVerifyByPhone = Consts::FALSE)
    {
        if (!$user) {
            throw ValidationException::withMessages(['verify_code' => [__(('auth.verify.error_code'))]]);
        }

        if (!$isVerifyByPhone && $user->isEmailVerificationCodeExpired()) {
            throw ValidationException::withMessages(['verify_code' => [__(('auth.verify.expired_code'))]]);
        }

        if ($isVerifyByPhone && $user->isPhoneNumberVerificationCodeExpired()) {
            throw ValidationException::withMessages(['verify_code' => [__(('auth.verify.expired_code'))]]);
        }
    }

    private function activeAndCreateBalanceForUser($user, $ip)
    {
        $user->status = Consts::USER_ACTIVE;
        $user->save();

        $device = $this->userService->getCurrentDevice('', $user->id);
        $device->latest_ip_address = $ip;
        $device->save();

        $this->userService->createNewUserBalance($user->id);
        AddKlaviyoMailList::dispatch($user);
    }

    /**
     * @SWG\Post(
     *   path="/v1/register/send-code",
     *   summary="Send register code signup for phone number",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendRegisterCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|valid_phone_number|unique_phone_number'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        try {
            $this->sendValidateCode($request->all());
            return $this->sendResponse([]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/register/confirm-code",
     *   summary="Confirm register code signup for phone number",
     *   tags={"V1.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
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
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function confirmRegisterCode(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'code' => 'required'
        ]);
        $request->merge(['phone_number' => PhoneUtils::formatPhoneNumber($request->phone_number)]);

        try {
            $data = $this->confirmValidateCode($request->phone_number, $request->code);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
