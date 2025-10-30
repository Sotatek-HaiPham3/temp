<?php

namespace App\Http\Controllers\API\V2;

use App\Http\Controllers\AppBaseController;
use App\Http\Services\UserService;
use App\Models\UserSecuritySetting;
use App\PhoneUtils;
use App\Traits\GenerateBearerTokenTrait;
use App\Traits\RegisterTrait;
use DB;
use Exception;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Jenssegers\Agent\Facades\Agent;
use Log;
use Mail;

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
     *   path="/v2/register/send-email-code",
     *   summary="Send register code signup for email",
     *   tags={"V2.Authentication"},
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
    public function sendEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email|max:255'
        ]);

        try {
            $this->sendEmailValidateCode($request->all());
            return $this->sendResponse([]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/register/confirm-email-code",
     *   summary="Confirm register code signup for email",
     *   tags={"V2.Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="email",
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
    public function confirmEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'code' => 'required'
        ]);

        try {
            $data = $this->confirmEmailValidateCode($request->email, $request->code);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
