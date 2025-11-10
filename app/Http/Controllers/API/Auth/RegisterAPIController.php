<?php

namespace App\Http\Controllers\API\Auth;

use App\Consts;
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

    use RegistersUsers, RegisterTrait;

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
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username'           => 'required|unique_username|string|min:3|max:20|special_characters',
            'email'              => 'required|string|email|max:190|unique_email|regex:/^[\w+\.-]+@([\w-]+\.)+[\w-]{2,4}$/',
            'password'           => 'required|string|min:6|max:72|regex:/^(?=.*[A-Z]).+$/|confirmed|password_white_space',
            // 'phone_number'       => 'required|string|max:15|phone:phone_country_code|unique_phone_number',
            'phone_number'       => 'required|string|max:17|unique_phone_number',
            'phone_country_code' => 'required_with:phone_number|string|max:5|valid_phone_contry_code',
            'agree_term'         => 'required',
            'dob'                => 'required|before:-13 years|date_format:d/m/Y',
            'sex'                => 'required'
        ]);
    }

    /**
     * @SWG\Post(
     *   path="/create-account",
     *   summary="Register",
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
     *       name="phone_number",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="phone_country_code",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="password",
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
     *  @SWG\Parameter(
     *       name="dob",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="sex",
     *       in="formData",
     *       required=true,
     *       type="number"
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
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        try {
            $this->doRegister($request->all());
            return $this->sendResponse('Ok');
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/send-verification-code-for-email",
     *   summary="Send verification code active account for email",
     *   tags={"Authentication"},
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

            Mail::queue(new VerificationMailQueue($user, Consts::DEFAULT_LOCALE, $request->all()));

            DB::commit();
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/send-verification-code-for-phone-number",
     *   summary="Send verification code active account for phone number",
     *   tags={"Authentication"},
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
    public function sendVerificationCodeForPhoneNumber(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|string|max:15|exists:users,phone_number'
        ]);

        DB::beginTransaction();
        try {
            $user = User::where('phone_number', $request->phone_number)->first();

            if ($user->phone_verified) {
                throw new PhoneNumberVerifiedException();
            }

            if (!PhoneUtils::allowSmsNotification($user)) {
                throw new ChangePhoneNumberException('exceptions.email_not_verified_first');
            }

            $confirmationCode = Utils::generateRandomString(Consts::VERIFY_CODE_LENGTH, Consts::VERIFY_CODE_STRING);
            $user->phone_verify_code = $confirmationCode;
            $user->phone_verify_created_at = Carbon::now();
            $user->save();

            SendSmsNotificationJob::dispatch($user, Consts::NOTIFY_SMS_VERIFY_CODE);

            event(new UserUpdated($user->id));

            DB::commit();
            return $this->sendResponse('ok');
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/verify-email",
     *   summary="Verify email",
     *   tags={"Authentication"},
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
     *   path="/verify-phone-number",
     *   summary="Verify phone number",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="phone_number",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="phone_country_code",
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
            // 'phone_number'         => 'required|string|max:15|phone:phone_country_code',
            'phone_number'         => 'required|string|max:17',
            'phone_country_code'   => 'required_with:phone_number|string|max:5|valid_phone_contry_code',
            'verify_code'          => 'required|digits:6'
        ])->validate();

        $params = [
            'phone_number' => $request->phone_number,
            'phone_verify_code' => $request->verify_code,
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
}
