<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AppBaseController;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Utils;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Carbon\Carbon;
use App\Exceptions\Reports\ResetPasswordTokenException;
use App\Exceptions\Reports\ResetPasswordUserException;
use App\Exceptions\Reports\UserNotExitstException;
use Mail;
use App\Mails\ChangePasswordMail;

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

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/login';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }


    protected function resetPassword($user, $password)
    {
        $user->password = Hash::make($password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));
    }

    protected function sendResetResponse($response)
    {
        session()->put('url.intended', $this->redirectPath());
        return redirect($this->redirectPath())
            ->with('status', trans($response));
    }

    /**
     * @SWG\Post(
     *   path="/execute-reset-password",
     *   summary="Execute reset password",
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
     *       name="username",
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
     *       name="token",
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
    public function resetViaApi(Request $request)
    {
        $this->validate($request, $this->rules(), $this->validationErrorMessages());
        $this->validateUser($request);
        $this->checkValidToken($request);

        $response = $this->broker()->reset(
            $this->credentials($request), function ($user, $password) {
                $this->resetPassword($user, $password);
            }
        );

        if ($response == Password::PASSWORD_RESET) {
            $user = User::where('email', $request->email)
                ->where('username', $request->username)->first();
            if(!$user) {
                throw new UserNotExitstException();
            }
            Mail::queue(new ChangePasswordMail($user));
            return $this->sendResponse($response);
        }
        throw ValidationException::withMessages(['error' => [__($response)]]);
    }

    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|string|email|max:255',
            'username' => 'required|string',
            'password' => 'required|string|min:6|max:72|regex:/(?=.*[A-Z]).+$/|confirmed|password_white_space',
        ];
    }

    private function validateUser($request)
    {
        $isValidUser = User::where('email', $request->email)
            ->where('username', $request->username)
            ->exists();
        if (! $isValidUser) {
            throw new ResetPasswordUserException();
        }
    }

    /**
     * @SWG\Get(
     *   path="/is-valid-token",
     *   summary="Check valid token when reset Password",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="email",
     *       in="query",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="token",
     *       in="query",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function checkValidToken(Request $request)
    {
        $token = $request->input('token');
        $email = $request->input('email');

        $record = \DB::table('password_resets')->where('email', $email)->first();
        if (is_null($record) || !Hash::check($token, $record->token) || (now()->subHour(1)->gt(Carbon::parse($record->created_at)))) {
            throw new ResetPasswordTokenException();
        }
        return 'ok';
    }
}
