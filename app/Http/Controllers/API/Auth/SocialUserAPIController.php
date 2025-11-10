<?php

namespace App\Http\Controllers\API\Auth;

use App;
use App\Consts;
use \Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\SocialUser;
use App\Http\Requests\SocialRequest;
use Socialite;
use App\Http\Services\MasterdataService;
use GuzzleHttp\Exception\ClientException;
use App\Exceptions\Reports\InvalidCredentialException;
use App\Exceptions\Reports\UserExistedException;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use App\Traits\RegisterTrait;

class SocialUserAPIController extends AccessTokenController
{
    use HandlesOAuthErrors, RegisterTrait;

    /**
     * @SWG\Post(
     *   path="/social/auth-token",
     *   summary="Login with Google, Facebook, Discord, Apple",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="provider",
     *       in="formData",
     *       enum={"google", "facebook", "discord", "apple"},
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
    public function authToken(Request $request)
    {
        $request->validate([
            'provider' => 'required|provider_valid',
            'token' => 'required',
        ]);

        try {
            $providerUser = Socialite::with($request->provider)->userFromToken($request->token);

            $socialUser = SocialUser::where('provider', $request->provider)->where('provider_id', $providerUser->id)->first();

            if ($socialUser) {
               $user = User::find($socialUser->user_id);
               return $this->authenticatedResponse($user);
            }

            return $this->sendResponse([
                'new_user' => Consts::TRUE,
                'email' => $providerUser->email,
                'username' => $providerUser->nickname,
                'name' => $providerUser->name,
                'avatar' => $providerUser->avatar
            ]);
        } catch (ClientException $e) {
            throw new InvalidCredentialException('invalid_token');
        }
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
     *   path="/social/register",
     *   summary="Login with Google, Facebook, Discord, Apple",
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
     *  @SWG\Parameter(
     *       name="provider",
     *       in="formData",
     *       enum={"google", "facebook", "discord", "apple"},
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
            $providerUser = Socialite::with($request->provider)->userFromToken($request->token);

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

            return $this->sendResponse('Ok');
        } catch(ClientException $e) {
            throw new InvalidCredentialException("invalid_{$request->provider}_token");
        }
    }

    private function authenticatedResponse($user)
    {
        $token = $user->createToken($user->full_name, ['*']);
        return [
                'access_token'  => $token->accessToken,
                'expires_in'    => Carbon::parse($token->token->expires_at)->timestamp,
                'secret'         => null,
                'refresh_token' => null,
                'locale'        => App::getLocale(),
                'token_type'    => 'Bearer'
        ];
    }
}
