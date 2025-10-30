<?php

namespace App\Http\Controllers\API\V21;

use App\Http\Controllers\AppBaseController as AppBase;
use App\Http\Services\UserService;
use Illuminate\Http\Request;

class UserAPIController extends AppBase
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @SWG\Get(
     *   path="/v2.1/user-info",
     *   summary="Get User Information by Username",
     *   tags={"V2.1.Users"},
     *   security={
     *   },
     *  @SWG\Parameter(
     *       name="username",
     *       in="query",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getUserInfoByUsername(Request $request)
    {
        $request->validate([
            'username' => 'required'
        ]);
        $username = $request->username;
        $data = $this->userService->getUserInfoByUsername($username);
        return $this->sendResponse($data);
    }
}
