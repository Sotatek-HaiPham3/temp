<?php

namespace App\Http\Controllers\API\V21;

use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateCommunityMessageFormRequest;
use App\Http\Requests\CreateCommunityRequest;
use App\Http\Requests\CreateVoiceChatRoomRequest;
use App\Http\Services\ChatService;
use App\Http\Services\CommunityService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Swagger\Annotations as SWG;

class CommunityAPIController extends AppBaseController
{
    protected $chatService;
    protected $communityService;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->communityService = new CommunityService();
    }

    /**
     * @SWG\Get(
     *   path="/v2.1/community/members/online",
     *   summary="Get community members",
     *   tags={"V2.1.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="query",required=true,type="string"),
     *   @SWG\Parameter(name="role",in="query",required=false,type="string"),
     *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
     *   @SWG\Parameter(name="search_key",in="query",required=false,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *              @SWG\Property(type="integer", property="current_page"),
     *              @SWG\Property(type="integer", property="total"),
     *              @SWG\Property(type="integer", property="last_page"),
     *              @SWG\Property(type="integer", property="per_page"),
     *              @SWG\Property(type="array", property="data",
     *                  @SWG\Items(
     *                      @SWG\Property(property="id", type="integer"),
     *                      @SWG\Property(property="community_id", type="integer"),
     *                      @SWG\Property(property="invited_user_id", type="integer"),
     *                      @SWG\Property(property="kicked_by", type="integer"),
     *                      @SWG\Property(property="user_id", type="integer"),
     *                      @SWG\Property(property="role", type="string"),
     *                      @SWG\Property(property="updated_at", type="string"),
     *                      @SWG\Property(property="viewed_at", type="string"),
     *                      @SWG\Property(property="user", type="object",
     *                        @SWG\Property(property="id", type="integer"),
     *                        @SWG\Property(property="email", type="string"),
     *                        @SWG\Property(property="username", type="string"),
     *                        @SWG\Property(property="sex", type="integer"),
     *                        @SWG\Property(property="user_type", type="integer"),
     *                        @SWG\Property(property="avatar", type="string"),
     *                        @SWG\Property(property="existsCreditCard", type="boolean"),
     *                        @SWG\Property(property="newEmail", type="string"),
     *                        @SWG\Property(property="newUsername", type="string"),
     *                        @SWG\Property(property="newPhoneNumber", type="string"),
     *                        @SWG\Property(property="isFirstLogin", type="boolean"),
     *                        @SWG\Property(property="isGamelancer", type="boolean"),
     *                        @SWG\Property(property="is_vip", type="boolean"),
     *                      )
     *                 )
     *             ),
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getCommunityMembersOnline(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        try {
            $data = $this->communityService->getCommunityMembersOnline($request->community_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
