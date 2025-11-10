<?php

namespace App\Http\Controllers\API\Auth;

use Mail;
use App;
use Location;
use App\Consts;
use App\Utils;
use App\Models\User;
use App\Mail\LoginNewIP;
use App\Mail\LoginNewDevice;
use App\Mails\WelcomeMail;
use App\Models\UserConnectionHistory;
use Illuminate\Http\Request;
use App\Utils\BearerToken;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use Laravel\Passport\Http\Controllers\HandlesOAuthErrors;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\DeviceParserAbstract;
use Zend\Diactoros\Response as Psr7Response;
use App\Http\Services\MasterdataService;
use App\Http\Services\UserService;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Route;
use App\Exceptions\Reports\InvalidCredentialException;
use App\Exceptions\Reports\InvalidRequestException;
use Exception;
use DB;
use Mattermost;
use Nodebb;

class LoginAPIController extends AccessTokenController
{
    use HandlesOAuthErrors;

    private $user;

    /**
     * Authorize a client to access the user's account.
     *
     * @param  \Psr\Http\Message\ServerRequestInterface  $request
     * @return \Illuminate\Http\Response
     */
    public function login(ServerRequestInterface $request)
    {
        try {
            $response = $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
            $this->verifyAdditinalSettings($request);
            return $this->authenticated($response);
        } catch (OAuthServerException $ex) {
            $msg = trans('auth.failed');
            if ($ex->getErrorType() == 'account_inactive') {
                $msg = $ex->getMessage();
            }
            throw new InvalidCredentialException($msg);
        }
    }

    public function refreshToken(ServerRequestInterface $request)
    {
        try {
            $response = $this->convertResponse(
                $this->server->respondToAccessTokenRequest($request, new Psr7Response)
            );
            return $this->modifyResponse($response);
        } catch (OAuthServerException $ex) {
            throw new InvalidRequestException($ex->getPayload()['error'], $ex->getMessage());
        }
    }

    protected function authenticated($response)
    {
        $request = request();
        $user = $this->getUser($request->get('username'));

        $userService = new UserService();

        $device = $userService->getCurrentDevice('', $user->id);

        $userRealIP = getOriginalClientIp();

        if ($user->isValid() && ($device->latest_ip_address !== $userRealIP || $device->isNewDevice) ) {
            $device->latest_ip_address = $userRealIP;
            $device->save();
        }

        $this->storeConnection($device);

        event(new \App\Events\UserOnline($user->id));

        if ($user->isFirstLogin) {
            Mail::queue(new WelcomeMail($user, Consts::DEFAULT_LOCALE));
        }

        return $this->modifyResponse($response);
    }

    private function storeConnection($device) {
        $connectionHistory = new UserConnectionHistory;

        $position = false;//Location::get($device->latest_ip_address);
        if ($position === false) {
            $connectionHistory->addresses = Consts::ADDRESS_UNKNOWN;
        } else {
            $connectionHistory->addresses = $position->cityName . ', ' . $position->countryName;
        }

        $connectionHistory->user_id = $device->user->id;
        $connectionHistory->device_id = $device->id;
        $connectionHistory->ip_address = getOriginalClientIp();
        $connectionHistory->created_at = Utils::currentMilliseconds();
        $connectionHistory->updated_at = Utils::currentMilliseconds();
        $connectionHistory->save();
    }

    protected function modifyResponse($response) {
        $content = $this->getResponseContent($response);
        $content['secret'] = $this->createTokenSecret($content);
        // $content['chat_token'] = $this->createMattermostToken($content);
        $content['locale'] = App::getLocale();

        // $nodebb = $this->createNodebbToken($content);
        return $response;
    }

    protected function getResponseContent($response) {
        return collect(json_decode($response->content()));
    }

    protected function createTokenSecret($content) {
        $token = BearerToken::fromJWT($content['access_token']);
        $token->secret = str_random(40);
        $token->save();
        return $token->secret;
    }

    // protected function createMattermostToken($content)
    // {
    //     try {
    //         $user = $this->getUser(request()->username);

    //         $mattermostToken = Mattermost::getTokenUser($user->email);

    //         $token = BearerToken::fromJWT($content['access_token']);
    //         $token->mattermost_token = $mattermostToken;
    //         $token->save();
    //         return $token->mattermost_token;
    //     } catch (Exception $ex) {
    //         logger()->error($ex);
    //         throw new InvalidRequestException(__('auth.failed'));
    //     }
    // }

    // protected function createNodebbToken($content)
    // {
    //     try {
    //         $user = $this->getUser(request()->username);

    //         if (empty($user->nodebbUser)) {
    //             return;
    //         }

    //         $nodebbToken = Nodebb::getTokenUser($user);

    //         $token = BearerToken::fromJWT($content['access_token']);
    //         $token->nodebb_token = $nodebbToken->payload->token;
    //         $token->save();
    //         return $nodebbToken;
    //     } catch (Exception $ex) {
    //         logger()->error($ex);
    //         throw new InvalidRequestException(__('auth.failed'));
    //     }
    // }

    protected function verifyAdditinalSettings($request) {
        $params = $request->getParsedBody();
        $username = $params['username'];
        $user = $this->getUser($username);

        if ($user->status === Consts::USER_INACTIVE) {
            throw new OAuthServerException(trans('auth.blocked'), 6, 'account_inactive');
        }
    }

    private function getUser($username)
    {
        return User::where(function ($query) use ($username) {
            $query->where('email', $username)
                ->orWhere('username', $username)
                ->orWhere('phone_number', $username);
            })
            ->first();
    }

    /**
     * @SWG\Post(
     *   path="/login",
     *   summary="Login",
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
     *       name="password",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function loginViaApi(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        request()->request->add([
            'grant_type' => 'password',
            'client_id' => 1,
            'client_secret' => env('CLIENT_SECRET'),
            'username' => $request->input('username'),
            'password' => $request->input('password'),
            'scope' => '*',
        ]);

        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $token = Request::create('api/oauth/token', 'POST');
        return Route::dispatch($token);
    }

    /**
     * @SWG\Put(
     *   path="/logout",
     *   summary="Logout",
     *   tags={"Authentication"},
     *   security={
      *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function logoutViaApi(Request $request)
    {
        DB::beginTransaction();

        try {
            Mattermost::closeDriver();
            Nodebb::closeDriver();

            $token = BearerToken::fromRequest();
            $token->revoked = Consts::TRUE;
            $token->mattermost_token = null;
            $token->save();

            DB::commit();

            $data = [
                'success' => true,
                'dataVersion' => MasterdataService::getDataVersion(),
                'data' => 'Ok',
            ];
            return response()->json($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
     * @SWG\Post(
     *   path="/refresh-token",
     *   summary="Refresh Token",
     *   tags={"Authentication"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="refresh_token",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function refreshTokenViaApi(Request $request)
    {
        $request->validate([
            'refresh_token' => 'required'
        ]);

        request()->request->add([
            'grant_type' => 'refresh_token',
            'client_id' => 1,
            'client_secret' => env('CLIENT_SECRET'),
            'refresh_token' => $request->input('refresh_token'),
            'scope' => '*',
        ]);

        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/x-www-form-urlencoded');

        $token = Request::create('api/oauth/refresh-token', 'POST');

        return Route::dispatch($token);
    }
}
