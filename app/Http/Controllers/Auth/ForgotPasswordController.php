<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\AppBaseController;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use App\Utils;
use App\Http\Services\MasterdataService;
use Jenssegers\Agent\Facades\Agent;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\PhoneUtils;
use App\Exceptions\Reports\ResetPasswordUserException;
use App\Exceptions\Reports\PhoneNumberNotSupportedException;
use Illuminate\Auth\Passwords\PasswordBroker;


class ForgotPasswordController extends AppBaseController
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * @SWG\Post(
     *   path="/reset-password",
     *   summary="Reset Password",
     *   tags={"Authentication"},
     *   security={
     *   },
     *   @SWG\Parameter(
     *       name="username",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Parameter(
     *       name="sendByPhoneNumber",
     *       in="formData",
     *       required=true,
     *       type="boolean"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function sendResetLinkEmailViaApi(Request $request) {

        $user = $this->validateUser($request);

        if ($request->sendByPhoneNumber && !PhoneUtils::allowSmsNotification($user)) {
            throw new PhoneNumberNotSupportedException();
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->sendResetLink(
            // $request->only('email')
            ['email' => $user->email],
            $request->sendByPhoneNumber,
            $request->timezoneOffset
        );

        if ($response == Password::INVALID_USER) {
            $this->throwValidationException();
        }

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResponse($user)
            : $this->sendError($response, 400);
    }

    /**
     * Send a password reset link to a user.
     *
     * @param  array  $credentials
     * @return string
     */
    public function sendResetLink(array $credentials, $sendByPhoneNumber, $timezoneOffset)
    {
        // First we will check to see if we found a user at the given credentials and
        // if we did not we will redirect back to this current URI with a piece of
        // "flash" data in the session to indicate to the developers the errors.
        $user = $this->broker()->getUser($credentials);

        if (is_null($user)) {
            return PasswordBroker::INVALID_USER;
        }

        // Once we have the reset token, we are ready to send the message out to this
        // user with a link to reset their password. We will then redirect back to
        // the current URI having nothing set in the session to indicate errors.
        $user->sendPasswordResetNotification(
            app(PasswordBroker::class)->createToken($user),
            $sendByPhoneNumber,
            $timezoneOffset
        );

        return PasswordBroker::RESET_LINK_SENT;
    }

    protected function validateUser(Request $request)
    {
        $this->validate($request, [
            // 'email' => 'required|email',
            'username' => 'required|max:190'
        ]);
        $user = $this->getUser($request);

        if (!$user) {
            $this->throwValidationException();
        }

        return $user;
    }

    private function throwValidationException()
    {
        throw new ResetPasswordUserException();
    }

    private function getUser(Request $request)
    {
        return User::where('email', $request->username)
            ->orwhere('username', $request->username)
            ->orwhere('phone_number', $request->username)
            ->first();
    }
}
