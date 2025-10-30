<?php

namespace App\Http\Controllers\API\V2;

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
     *   path="/v2/community/list",
     *   summary="Get list public group community",
     *   tags={"V2.Group community"},
     *   security={},
     *   @SWG\Parameter(name="search_key",in="query",required=false,type="string"),
     *   @SWG\Response(
     *        response=200,
     *        description="Success",
     *        @SWG\Schema(
     *             title="success",
     *             @SWG\Property(property="success", type="boolean"),
     *             @SWG\Property(property="dataVersion", type="string"),
     *             @SWG\Property(property="data", type="object",
     *               @SWG\Property(type="integer", property="current_page"),
     *               @SWG\Property(type="integer", property="total"),
     *               @SWG\Property(type="integer", property="last_page"),
     *               @SWG\Property(type="integer", property="per_page"),
     *               @SWG\Property(type="array", property="data",
     *                   @SWG\Items(
     *                       @SWG\Property(property="id", type="integer"),
     *                       @SWG\Property(property="description", type="string"),
     *                       @SWG\Property(property="slug", type="string"),
     *                       @SWG\Property(property="name", type="string"),
     *                       @SWG\Property(property="photo", type="string"),
     *                       @SWG\Property(property="gallery_id", type="integer"),
     *                       @SWG\Property(property="mattermost_channel_id", type="string"),
     *                       @SWG\Property(property="is_private", type="integer"),
     *                       @SWG\Property(property="community_member_count", type="integer"),
     *                       @SWG\Property(property="creator_id", type="integer"),
     *                       @SWG\Property(property="total_users", type="integer"),
     *                       @SWG\Property(property="leader_count", type="integer"),
     *                       @SWG\Property(property="member_count", type="integer"),
     *                       @SWG\Property(property="total_request", type="integer"),
     *                       @SWG\Property(property="total_rooms", type="integer"),
     *                       @SWG\Property(property="total_rooms_size", type="integer"),
     *                       @SWG\Property(property="total_rooms_user", type="integer"),
     *                       @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                       @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                       @SWG\Property(property="created_at", type="string"),
     *                       @SWG\Property(property="updated_at", type="string"),
     *                       @SWG\Property(property="deleted_at", type="string")
     *                  ),
     *              ),
     *           )
     *        )
     *    ),
     *    @SWG\Response(response=404, description="Not Found"),
     *    @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getCommunities(Request $request)
    {
        $data = $this->communityService->getCommunities($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/my-community",
     *   summary="Get list my group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="search_key",in="query",required=false,type="string"),
     *  @SWG\Response(
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
     *                @SWG\Items(
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="description", type="string"),
     *                  @SWG\Property(property="slug", type="string"),
     *                  @SWG\Property(property="name", type="string"),
     *                  @SWG\Property(property="photo", type="string"),
     *                  @SWG\Property(property="gallery_id", type="integer"),
     *                  @SWG\Property(property="total_users", type="integer"),
     *                  @SWG\Property(property="leader_count", type="integer"),
     *                  @SWG\Property(property="member_count", type="integer"),
     *                  @SWG\Property(property="total_request", type="integer"),
     *                  @SWG\Property(property="total_rooms", type="integer"),
     *                  @SWG\Property(property="total_rooms_size", type="integer"),
     *                  @SWG\Property(property="total_rooms_user", type="integer"),
     *                  @SWG\Property(property="mattermost_channel_id", type="string"),
     *                  @SWG\Property(property="is_private", type="integer"),
     *                  @SWG\Property(property="community_member_count", type="integer"),
     *                  @SWG\Property(property="creator_id", type="integer"),
     *                  @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                  @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                  @SWG\Property(property="created_at", type="string"),
     *                  @SWG\Property(property="updated_at", type="string"),
     *                  @SWG\Property(property="deleted_at", type="string"),
     *                ),
     *             ),
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getMyCommunities(Request $request)
    {
        $data = $this->communityService->getMyCommunities($request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/create",
     *   summary="create group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="name", required=true, in="formData", type="string"),
     *   @SWG\Parameter(name="description", required=false, in="formData", type="string"),
     *   @SWG\Parameter(name="photo", required=false, in="formData", type="string"),
     *   @SWG\Parameter(name="background", required=false, in="formData", type="string"),
     *   @SWG\Parameter(name="is_private", required=true, in="formData", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="community_member_count", type="integer", default=100),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="total_request", type="integer"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *                @SWG\Property(property="total_rooms_size", type="integer"),
     *                @SWG\Property(property="total_rooms_user", type="integer"),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function store(CreateCommunityRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->communityService->createCommunity($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/update",
     *   summary="Update group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="id", in="formData", required=true, type="integer"),
     *   @SWG\Parameter(name="description", in="formData", type="string"),
     *   @SWG\Parameter(name="photo", in="formData", type="string"),
     *   @SWG\Parameter(name="background", in="formData", type="string"),
     *   @SWG\Parameter(name="is_private", in="formData", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="total_request", type="integer"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *                @SWG\Property(property="total_rooms_size", type="integer"),
     *                @SWG\Property(property="total_rooms_user", type="integer"),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="community_member_count", type="integer", default=100),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:communities,id',
            'description' => 'nullable|max:500',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->updateCommunity($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @SWG\Delete(
     *   path="/v2/community/delete",
     *   summary="delete a group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="id", required=true, in="formData", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     * @SWG\Response(response=401, description="Unauthenticated"),
     * @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function destroy(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->removeCommunity($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/deactivate",
     *   summary="deactivate a group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="total_request", type="integer"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *                @SWG\Property(property="total_rooms_size", type="integer"),
     *                @SWG\Property(property="total_rooms_user", type="integer"),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="community_member_count", type="integer", default=100),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function deactivate(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->deactivateCommunity($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/reactivate",
     *   summary="reactivate a group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="total_request", type="integer"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *                @SWG\Property(property="total_rooms_size", type="integer"),
     *                @SWG\Property(property="total_rooms_user", type="integer"),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="community_member_count", type="integer", default=100),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function reactivate(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->reactivateCommunity($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @SWG\Put(
     *   path="/v2/community/make-leader",
     *   summary="Make member become a moderator",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="role", type="string", description="owner | leader | member"),
     *                @SWG\Property(property="viewed_at", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="user", type="object",
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="avatar", type="string"),
     *                  @SWG\Property(property="is_vip", type="integer"),
     *                  @SWG\Property(property="online_setting", type="integer"),
     *                  @SWG\Property(property="sex", type="integer"),
     *                  @SWG\Property(property="user_type", type="integer"),
     *                  @SWG\Property(property="username", type="string"),
     *                )
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function makeLeader(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->makeLeader($request->user_id, $request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Put(
     *   path="/v2/community/remove-leader",
     *   summary="Make leader become a member",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="role", type="string", description="owner | leader | member"),
     *                @SWG\Property(property="viewed_at", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="user", type="object",
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="avatar", type="string"),
     *                  @SWG\Property(property="is_vip", type="integer"),
     *                  @SWG\Property(property="online_setting", type="integer"),
     *                  @SWG\Property(property="sex", type="integer"),
     *                  @SWG\Property(property="user_type", type="integer"),
     *                  @SWG\Property(property="username", type="string"),
     *                )
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function removeLeader(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->removeLeader($request->user_id, $request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/report",
     *   summary="Report Group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="reason_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="details",in="formData",required=false,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="reporter_id", type="integer", default=1),
     *                @SWG\Property(property="reason_id", type="integer", default=1),
     *                @SWG\Property(property="details", type="string"),
     *                @SWG\Property(property="status", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function reportCommunity(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'reason_id' => 'required',
            'details' => 'nullable|string|max:500|min:24'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->reportCommunity($request->community_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/request/list",
     *   summary="List request join to community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(name="community_id",in="query",required=true,type="integer"),
     *  @SWG\Response(
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
     *               @SWG\Property(type="array", property="data",
     *                   @SWG\Items(
     *                      @SWG\Property(property="id", type="integer"),
     *                      @SWG\Property(property="user_id", type="integer"),
     *                      @SWG\Property(property="community_id", type="integer"),
     *                      @SWG\Property(property="message", type="string"),
     *                      @SWG\Property(property="status", type="string"),
     *                      @SWG\Property(property="created_at", type="string"),
     *                      @SWG\Property(property="updated_at", type="string"),
     *                      @SWG\Property(property="user", type="object",
     *                          @SWG\Property(property="id", type="integer"),
     *                          @SWG\Property(property="email", type="string"),
     *                          @SWG\Property(property="username", type="string"),
     *                          @SWG\Property(property="sex", type="string"),
     *                          @SWG\Property(property="avatar", type="string"),
     *                      ),
     *                  ),
     *             ),
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getRequests(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        $data = $this->communityService->getRequests($request->community_id, $request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Put(
     *   path="/v2/community/exit",
     *   summary="Exit group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="role", type="string", description="owner | leader | member"),
     *                @SWG\Property(property="viewed_at", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="user", type="object",
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="avatar", type="string"),
     *                  @SWG\Property(property="is_vip", type="integer"),
     *                  @SWG\Property(property="online_setting", type="integer"),
     *                  @SWG\Property(property="sex", type="integer"),
     *                  @SWG\Property(property="user_type", type="integer"),
     *                  @SWG\Property(property="username", type="string"),
     *                )
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function exitCommunity(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->exitCommunity($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/request/accept",
     *   summary="Accept request to join group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function acceptRequestToJoin(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'user_id' => 'required|exists:community_requests,user_id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->acceptRequestToJoin($request->community_id, $request->user_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/request/reject",
     *   summary="Reject request to join group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function rejectRequestToJoin(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'user_id' => 'required|exists:community_requests,user_id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->rejectRequestToJoin($request->community_id, $request->user_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/request/cancel",
     *   summary="Cancel request to join group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function cancelRequestToJoin(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->cancelRequestToJoin($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Put(
     *   path="/v2/community/kick",
     *   summary="Kick user out group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function kickUser(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'user_id' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->kickUser($request->community_id, $request->user_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post/reaction",
     *   summary="Reaction to a post group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="post_id",in="formData",required=true,type="string",description="Mattermost post_id."),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function reactionPost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'post_id' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->reactionPost($request->community_id, $request->post_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Delete(
     *   path="/v2/community/post/reaction",
     *   summary="Remove a reaction from a post",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="post_id",in="formData",required=true,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function deleteReactionPost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'post_id' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->deleteReactionPost($request->community_id, $request->post_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/invite/list",
     *   summary="Get Invite List For Group Community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="query",required=true,type="integer"),
     *   @SWG\Response(
     *        response=200,
     *        description="Success",
     *        @SWG\Schema(
     *             title="success",
     *             @SWG\Property(property="success", type="boolean"),
     *             @SWG\Property(property="dataVersion", type="string"),
     *              @SWG\Property(type="array", property="data",
     *                  @SWG\Items(
     *                      @SWG\Property(property="id", type="integer"),
     *                      @SWG\Property(property="avatar", type="string"),
     *                      @SWG\Property(property="sex", type="integer"),
     *                      @SWG\Property(property="username", type="string"),
     *                      @SWG\Property(property="user_type", type="integer"),
     *                      @SWG\Property(property="online_setting", type="integer"),
     *                      @SWG\Property(property="invited", type="boolean"),
     *                      @SWG\Property(property="existsCreditCard", type="boolean"),
     *                      @SWG\Property(property="newEmail", type="string"),
     *                      @SWG\Property(property="newUsername", type="string"),
     *                      @SWG\Property(property="newPhoneNumber", type="string"),
     *                      @SWG\Property(property="isFirstLogin", type="boolean"),
     *                      @SWG\Property(property="isGamelancer", type="boolean"),
     *              )
     *           )
     *        )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getInviteList(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        try {
            $data = $this->communityService->getInviteList($request->community_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/invite/create",
     *   summary="Invite user into Group Community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function inviteUser(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'user_id' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->inviteUser($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/join",
     *   summary="Join community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="invited_user", type="string", default=1),
     *                @SWG\Property(property="invited_user_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="role", type="string", description="owner | leader | member"),
     *                @SWG\Property(property="user", type="object",
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="avatar", type="string"),
     *                  @SWG\Property(property="is_vip", type="integer"),
     *                  @SWG\Property(property="online_setting", type="integer"),
     *                  @SWG\Property(property="sex", type="integer"),
     *                  @SWG\Property(property="user_type", type="integer"),
     *                  @SWG\Property(property="username", type="string"),
     *                )
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function joinCommunity(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->joinCommunity($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post/report",
     *   summary="Report Group community message",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="user_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="post_id",in="formData",required=true,type="string",description="Mattermost post_id."),
     *   @SWG\Parameter(name="reason_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="details",in="formData",required=false,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="reporter_id", type="integer", default=1),
     *                @SWG\Property(property="reason_id", type="integer", default=1),
     *                @SWG\Property(property="details", type="string"),
     *                @SWG\Property(property="status", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function reportPost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'user_id' => 'required|exists:users,id',
            'post_id' => 'required',
            'reason_id' => 'required',
            'details' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->reportPost($request->community_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/detail",
     *   summary="Get detail Community",
     *   tags={"V2.Group community"},
     *   security={
     *   },
     *   @SWG\Parameter(name="slug",in="query",required=true,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *                @SWG\Property(property="total_rooms_size", type="integer"),
     *                @SWG\Property(property="total_rooms_user", type="integer"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="list_leader_role", type="array", description="List role owner and leader member",
     *                  @SWG\Items(
     *                      @SWG\Property(property="id", type="integer", default=1),
     *                      @SWG\Property(property="community_id", type="integer", default=1),
     *                      @SWG\Property(property="user_id", type="integer", default=1),
     *                      @SWG\Property(property="viewed_at", type="integer", default=1),
     *                      @SWG\Property(property="invited_user_id", type="integer", default=1),
     *                      @SWG\Property(property="kicked_by", type="integer", default=1),
     *                      @SWG\Property(property="role", type="string"),
     *                      @SWG\Property(property="deleted_at", type="string"),
     *                      @SWG\Property(property="created_at", type="string"),
     *                      @SWG\Property(property="updated_at", type="string"),
     *                  )
     *              ),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getCommunityDetail(Request $request)
    {
        $request->validate([
            'slug' => 'required|exists:communities,slug'
        ]);

        try {
            $data = $this->communityService->getCommunityDetail($request->slug);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/members",
     *   summary="Get community members",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="query",required=true,type="string"),
     *   @SWG\Parameter(name="role",in="query",required=false,type="string"),
     *   @SWG\Parameter(name="limit",in="query",required=false,type="integer"),
     *   @SWG\Parameter(name="search_key",in="query",required=false,type="string"),
     *  @SWG\Response(
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
    public function getCommunityMembers(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        try {
            $data = $this->communityService->getCommunityMembers($request->community_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/community-existed",
     *   summary="Check channel existed",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="slug",in="formData",required=true,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="mattermost_channel_id", type="string"),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="slug", type="string"),
     *                @SWG\Property(property="description", type="string"),
     *                @SWG\Property(property="photo", type="string"),
     *                @SWG\Property(property="gallery_id", type="integer"),
     *                @SWG\Property(property="total_users", type="integer"),
     *                @SWG\Property(property="leader_count", type="integer"),
     *                @SWG\Property(property="member_count", type="integer"),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="status", type="string", description="active | deactivated | deleted"),
     *                @SWG\Property(property="inactive_at", type="string", description="Format is date-time Ex: 2021-11-01 09:30:25"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="total_rooms", type="integer"),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkCommunityExisted(Request $request)
    {
        $request->validate([
            'slug' => 'required'
        ]);

        try {
            $data = $this->communityService->checkCommunityExisted($request->slug);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/invite/accept",
     *   summary="Accept invite to join group community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="invited_user_id", type="integer", default=1),
     *                @SWG\Property(property="kicked_by", type="integer", default=1),
     *                @SWG\Property(property="role", type="string", description="owner | leader | member"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="viewed_at", type="string"),
     *                @SWG\Property(property="user", type="object",
     *                  @SWG\Property(property="id", type="integer"),
     *                  @SWG\Property(property="email", type="string"),
     *                  @SWG\Property(property="username", type="string"),
     *                  @SWG\Property(property="sex", type="integer"),
     *                  @SWG\Property(property="user_type", type="integer"),
     *                  @SWG\Property(property="avatar", type="string"),
     *                  @SWG\Property(property="existsCreditCard", type="boolean"),
     *                  @SWG\Property(property="newEmail", type="string"),
     *                  @SWG\Property(property="newUsername", type="string"),
     *                  @SWG\Property(property="newPhoneNumber", type="string"),
     *                  @SWG\Property(property="isFirstLogin", type="boolean"),
     *                  @SWG\Property(property="isGamelancer", type="boolean"),
     *                  @SWG\Property(property="is_vip", type="boolean"),
     *                )
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function acceptInvite(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->acceptInvite($request->community_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/check-random-room",
     *   summary="Check Random Room",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="formData",required=true,type="integer"),
     *   @SWG\Parameter(name="sid",in="formData",required=false,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="background_url", type="string"),
     *                @SWG\Property(property="code", type="string"),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="creator_id", type="integer", default=1),
     *                @SWG\Property(property="current_size", type="integer", default=1),
     *                @SWG\Property(property="game_id", type="integer", default=1),
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="is_private", type="integer", default=1),
     *                @SWG\Property(property="name", type="string"),
     *                @SWG\Property(property="pinned", type="integer", default=1),
     *                @SWG\Property(property="region", type="string"),
     *                @SWG\Property(property="rules", type="string"),
     *                @SWG\Property(property="size", type="integer"),
     *                @SWG\Property(property="status", type="string"),
     *                @SWG\Property(property="title", type="string"),
     *                @SWG\Property(property="topic", type="string"),
     *                @SWG\Property(property="topic_id", type="integer", default=1),
     *                @SWG\Property(property="type", type="string"),
     *                @SWG\Property(property="updated_at", type="string"),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkRandomRoom(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->checkRandomRoom($request->community_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/my-role",
     *   summary="Get my role in community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id",in="query",required=true,type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                @SWG\Property(property="id", type="integer", default=1),
     *                @SWG\Property(property="community_id", type="integer", default=1),
     *                @SWG\Property(property="user_id", type="integer", default=1),
     *                @SWG\Property(property="viewed_at", type="string"),
     *                @SWG\Property(property="invited_user_id", type="integer"),
     *                @SWG\Property(property="role", type="string"),
     *                @SWG\Property(property="kicked_by", type="integer"),
     *                @SWG\Property(property="deleted_at", type="string"),
     *                @SWG\Property(property="created_at", type="string"),
     *                @SWG\Property(property="updated_at", type="string")
     *          ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getMyRole(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        try {
            $data = $this->communityService->getMyRole($request->community_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/posts-for-channel",
     *   summary="get all posts in channel",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="channel_id", required=true, in="query", type="string", description="mattermost channel id."),
     *   @SWG\Parameter(name="prev_post_id", required=false, in="query", type="string", description="A post id to select the posts that came before this one."),
     *   @SWG\Parameter(name="next_post_id", required=false, in="query", type="string", description="A post id to select the posts that came after this one"),
     *   @SWG\Parameter(name="page", required=false, in="query", type="integer", description="The page to select (start = 1)"),
     *   @SWG\Parameter(name="limit", required=false, in="query", type="integer", description="The number of posts per page."),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *              @SWG\Property(type="string", property="next_post_id"),
     *              @SWG\Property(type="string", property="prev_post_id"),
     *              @SWG\Property(type="array", property="order",
     *                  @SWG\Items()
     *              ),
     *              @SWG\Property(type="object", property="posts",
     *                  @SWG\Property(property="channel_id", type="string"),
     *                  @SWG\Property(property="create_at", type="integer"),
     *                  @SWG\Property(property="delete_at", type="integer"),
     *                  @SWG\Property(property="edit_at", type="integer"),
     *                  @SWG\Property(property="hashtags", type="string"),
     *                  @SWG\Property(property="id", type="string"),
     *                  @SWG\Property(property="is_pinned", type="boolean"),
     *                  @SWG\Property(property="message", type="string"),
     *                  @SWG\Property(property="metadata", type="object",
     *                      @SWG\Property(type="array", property="reactions",
     *                          @SWG\Items(
     *                              @SWG\Property(property="user_id", type="string"),
     *                              @SWG\Property(property="post_id", type="string"),
     *                              @SWG\Property(property="emoji_name", type="string"),
     *                              @SWG\Property(property="create_at", type="integer"),
     *                          )
     *                      ),
     *                  ),
     *                  @SWG\Property(property="original_id", type="string"),
     *                  @SWG\Property(property="parent_id", type="string"),
     *                  @SWG\Property(property="pending_post_id", type="string"),
     *                  @SWG\Property(property="reply_count", type="integer"),
     *                  @SWG\Property(property="root_id", type="string"),
     *                  @SWG\Property(property="type", type="string"),
     *                  @SWG\Property(property="update_at", type="integer"),
     *                  @SWG\Property(property="user_id", type="string"),
     *                  @SWG\Property(property="props", type="object"),
     *                  @SWG\Property(property="root_post", type="object",
     *                      @SWG\Property(property="channel_id", type="string"),
     *                      @SWG\Property(property="create_at", type="integer"),
     *                      @SWG\Property(property="delete_at", type="integer"),
     *                      @SWG\Property(property="edit_at", type="integer"),
     *                      @SWG\Property(property="hashtags", type="string"),
     *                      @SWG\Property(property="id", type="string"),
     *                      @SWG\Property(property="is_pinned", type="boolean"),
     *                      @SWG\Property(property="message", type="string"),
     *                      @SWG\Property(property="metadata", type="object",
     *                          @SWG\Property(type="array", property="reactions",
     *                              @SWG\Items(
     *                                  @SWG\Property(property="user_id", type="string"),
     *                                  @SWG\Property(property="post_id", type="string"),
     *                                  @SWG\Property(property="emoji_name", type="string"),
     *                                  @SWG\Property(property="create_at", type="integer"),
     *                              )
     *                          ),
     *                      ),
     *                      @SWG\Property(property="original_id", type="string"),
     *                      @SWG\Property(property="parent_id", type="string"),
     *                      @SWG\Property(property="pending_post_id", type="string"),
     *                      @SWG\Property(property="reply_count", type="integer"),
     *                      @SWG\Property(property="root_id", type="string"),
     *                      @SWG\Property(property="type", type="string"),
     *                      @SWG\Property(property="update_at", type="integer"),
     *                      @SWG\Property(property="user_id", type="string"),
     *                      @SWG\Property(property="props", type="object")
     *                  )
     *             )
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getPostsForChannel(Request $request)
    {
        $request->validate([
            "channel_id" => 'required|exists:communities,mattermost_channel_id'
        ]);

        $data = $this->communityService->getPostsForCommunityById($request->channel_id, $request->all());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post",
     *   summary="create Post message",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string", description="mattermost channel id."),
     *   @SWG\Parameter(name="root_id", required=false, in="formData", type="string", description="The post ID to reply on"),
     *   @SWG\Property(
     *       type="array",
     *       property="images",
     *       @SWG\Items(type="string", description="Image path"),
     *   ),
     *   @SWG\Parameter(name="message", required=true, in="formData", type="string"),
     *  @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *               @SWG\Property(property="channel_id", type="string"),
     *               @SWG\Property(property="create_at", type="integer"),
     *               @SWG\Property(property="delete_at", type="integer"),
     *               @SWG\Property(property="edit_at", type="integer"),
     *               @SWG\Property(property="hashtags", type="string"),
     *               @SWG\Property(property="id", type="string"),
     *               @SWG\Property(property="is_pinned", type="boolean"),
     *               @SWG\Property(property="message", type="string"),
     *               @SWG\Property(property="metadata", type="object"),
     *               @SWG\Property(property="original_id", type="string"),
     *               @SWG\Property(property="parent_id", type="string"),
     *               @SWG\Property(property="pending_post_id", type="string"),
     *               @SWG\Property(property="reply_count", type="integer"),
     *               @SWG\Property(property="root_id", type="string"),
     *               @SWG\Property(property="type", type="string"),
     *               @SWG\Property(property="update_at", type="integer"),
     *               @SWG\Property(property="user_id", type="string"),
     *               @SWG\Property(property="props", type="object",
     *                   @SWG\Property(property="temp_id", type="string"),
     *                   @SWG\Property(property="user_id", type="string"),
     *                   @SWG\Property(property="user", type="object",
     *                     @SWG\Property(property="id", type="integer"),
     *                     @SWG\Property(property="avatar", type="string"),
     *                     @SWG\Property(property="is_vip", type="integer"),
     *                     @SWG\Property(property="online_setting", type="integer"),
     *                     @SWG\Property(property="sex", type="integer"),
     *                     @SWG\Property(property="user_type", type="integer"),
     *                     @SWG\Property(property="username", type="string"),
     *                   )
     *               ),
     *               @SWG\Property(property="user", type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="avatar", type="string"),
     *                 @SWG\Property(property="is_vip", type="integer"),
     *                 @SWG\Property(property="online_setting", type="integer"),
     *                 @SWG\Property(property="sex", type="integer"),
     *                 @SWG\Property(property="user_type", type="integer"),
     *                 @SWG\Property(property="username", type="string"),
     *               )
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function createPost(CreateCommunityMessageFormRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->communityService->createPost($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post/pin",
     *   summary="Pin Post",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Parameter(name="post_id", required=true, in="formData", type="string", description="mattermost post id"),
     *  @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *               @SWG\Property(property="channel_id", type="string"),
     *               @SWG\Property(property="create_at", type="integer"),
     *               @SWG\Property(property="delete_at", type="integer"),
     *               @SWG\Property(property="edit_at", type="integer"),
     *               @SWG\Property(property="hashtags", type="string"),
     *               @SWG\Property(property="id", type="string"),
     *               @SWG\Property(property="is_pinned", type="boolean"),
     *               @SWG\Property(property="message", type="string"),
     *               @SWG\Property(property="metadata", type="object"),
     *               @SWG\Property(property="original_id", type="string"),
     *               @SWG\Property(property="parent_id", type="string"),
     *               @SWG\Property(property="pending_post_id", type="string"),
     *               @SWG\Property(property="reply_count", type="integer"),
     *               @SWG\Property(property="root_id", type="string"),
     *               @SWG\Property(property="type", type="string"),
     *               @SWG\Property(property="update_at", type="integer"),
     *               @SWG\Property(property="user_id", type="string"),
     *               @SWG\Property(property="props", type="object",
     *                   @SWG\Property(property="temp_id", type="string"),
     *                   @SWG\Property(property="user_id", type="string"),
     *                   @SWG\Property(property="user", type="object",
     *                     @SWG\Property(property="id", type="integer"),
     *                     @SWG\Property(property="avatar", type="string"),
     *                     @SWG\Property(property="is_vip", type="integer"),
     *                     @SWG\Property(property="online_setting", type="integer"),
     *                     @SWG\Property(property="sex", type="integer"),
     *                     @SWG\Property(property="user_type", type="integer"),
     *                     @SWG\Property(property="username", type="string"),
     *                   )
     *               )
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function pinPost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'post_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->pinPost($request->community_id, $request->post_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post/unpin",
     *   summary="Unpin Post",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Parameter(name="post_id", required=true, in="formData", type="string", description="mattermost post id"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function unpinPost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'post_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->unpinPost($request->community_id, $request->post_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/post/pinned",
     *   summary="Pinned Posts",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string", description="mattermost channel id"),
     *  @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *              @SWG\Property(type="string", property="next_post_id"),
     *              @SWG\Property(type="string", property="prev_post_id"),
     *              @SWG\Property(type="array", property="order",
     *                  @SWG\Items()
     *              ),
     *              @SWG\Property(type="object", property="posts",
     *               @SWG\Property(property="channel_id", type="string"),
     *               @SWG\Property(property="create_at", type="integer"),
     *               @SWG\Property(property="delete_at", type="integer"),
     *               @SWG\Property(property="edit_at", type="integer"),
     *               @SWG\Property(property="hashtags", type="string"),
     *               @SWG\Property(property="id", type="string"),
     *               @SWG\Property(property="is_pinned", type="boolean"),
     *               @SWG\Property(property="message", type="string"),
     *               @SWG\Property(property="metadata", type="object"),
     *               @SWG\Property(property="original_id", type="string"),
     *               @SWG\Property(property="parent_id", type="string"),
     *               @SWG\Property(property="pending_post_id", type="string"),
     *               @SWG\Property(property="reply_count", type="integer"),
     *               @SWG\Property(property="root_id", type="string"),
     *               @SWG\Property(property="type", type="string"),
     *               @SWG\Property(property="update_at", type="integer"),
     *               @SWG\Property(property="user_id", type="string"),
     *               @SWG\Property(property="props", type="object",
     *                   @SWG\Property(property="temp_id", type="string"),
     *                   @SWG\Property(property="user_id", type="string"),
     *                   @SWG\Property(property="user", type="object",
     *                     @SWG\Property(property="id", type="integer"),
     *                     @SWG\Property(property="avatar", type="string"),
     *                     @SWG\Property(property="is_vip", type="integer"),
     *                     @SWG\Property(property="online_setting", type="integer"),
     *                     @SWG\Property(property="sex", type="integer"),
     *                     @SWG\Property(property="user_type", type="integer"),
     *                     @SWG\Property(property="username", type="string"),
     *                   )
     *               )
     *             ),
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getPinnedPosts(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            "channel_id" => 'required|exists:communities,mattermost_channel_id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->getPinnedPosts($request->community_id, $request->channel_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Delete(
     *   path="/v2/community/post/delete",
     *   summary="Delete Post",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Parameter(name="post_id", required=true, in="formData", type="string", description="mattermost post id"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean"),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function deletePost(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'post_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->deletePost($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/name-change/list",
     *   summary="Get Community name change request",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="query", type="integer"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="community_id", type="integer"),
     *                  @SWG\Property(property="request_user_id", type="integer"),
     *                  @SWG\Property(property="reason_id", type="integer"),
     *                  @SWG\Property(property="old_name", type="string"),
     *                  @SWG\Property(property="new_name", type="string"),
     *                  @SWG\Property(property="status", type="string"),
     *                  @SWG\Property(property="updated_at", type="string"),
     *                  @SWG\Property(property="created_at", type="string"),
     *                  @SWG\Property(property="id", type="integer"),
     *            ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getNameChangeRequest(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
        ]);

        try {
            $data = $this->communityService->getNameChangeRequest($request->community_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/name-change/create",
     *   summary="Community name change request",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer"),
     *   @SWG\Parameter(name="reason_id", required=true, in="formData", type="integer", description="Id for reason table"),
     *   @SWG\Parameter(name="new_name", required=true, in="formData", type="string"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="community_id", type="integer"),
     *                  @SWG\Property(property="request_user_id", type="integer"),
     *                  @SWG\Property(property="reason_id", type="integer"),
     *                  @SWG\Property(property="old_name", type="string"),
     *                  @SWG\Property(property="new_name", type="string"),
     *                  @SWG\Property(property="status", type="string"),
     *                  @SWG\Property(property="updated_at", type="string"),
     *                  @SWG\Property(property="created_at", type="string"),
     *                  @SWG\Property(property="id", type="integer"),
     *            ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function nameChangeRequest(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'reason_id' => 'required|exists:reasons,id',
            'new_name' => 'required|min:8|max:64|unique:communities,name',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->nameChangeRequest($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/name-change/cancel",
     *   summary="Cancel Community name change request",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="id", required=true, in="formData", type="integer", description="ID for name request change"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="community_id", type="integer"),
     *                  @SWG\Property(property="request_user_id", type="integer"),
     *                  @SWG\Property(property="reason_id", type="integer"),
     *                  @SWG\Property(property="old_name", type="string"),
     *                  @SWG\Property(property="new_name", type="string"),
     *                  @SWG\Property(property="status", type="string"),
     *                  @SWG\Property(property="updated_at", type="string"),
     *                  @SWG\Property(property="created_at", type="string"),
     *                  @SWG\Property(property="id", type="integer"),
     *            ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function cancelNameChangeRequest(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:community_name_change_requests,id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->cancelNameChangeRequest($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/check-user-join-request",
     *   summary="check User Join Request community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="id",in="query",required=true,type="number"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="has_requested", type="boolean")
     *            ),
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkUserJoinRequest(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:communities,id',
        ]);

        $data = $this->communityService->checkUserJoinRequest($request->id);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/report-user",
     *   summary="Report User In Community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer", description="ID of Community"),
     *   @SWG\Parameter(name="reported_user", required=true, in="formData", type="integer", description="ID of reported user"),
     *   @SWG\Parameter(name="reason_id", required=true, in="formData", type="integer", description="ID of reason"),
     *   @SWG\Parameter(name="details", required=false, in="formData", type="string", description="text for details of report"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean")
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function reportUser(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'reported_user' => 'required',
            'reason_id' => 'required',
            'details' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->reportUser($request->community_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/check-user-report-existed",
     *   summary="Check Report User In Community Existed",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="query", type="integer", description="ID of Community"),
     *   @SWG\Parameter(name="reported_user", required=true, in="query", type="integer", description="ID of reported user"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean")
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkUserReportExisted(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            'reported_user' => 'required',
        ]);

        $data = $this->communityService->checkUserReportExisted($request->community_id, $request->reported_user);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/check-community-report-existed",
     *   summary="Check Report Community In Community Existed",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="query", type="integer", description="ID of Community"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean")
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function checkCommunityReportExisted(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id'
        ]);

        $data = $this->communityService->checkCommunityReportExisted($request->community_id);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v2/community/post/unpin-all",
     *   summary="unpin all message in community",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="community_id", required=true, in="formData", type="integer", description="Community id"),
     *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string", description="mattermost channel id"),
     *   @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="Success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="boolean")
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function unPinAllPosts(Request $request)
    {
        $request->validate([
            'community_id' => 'required|exists:communities,id',
            "channel_id" => 'required|exists:communities,mattermost_channel_id',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->communityService->unpinAllPosts($request->community_id, $request->channel_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * @SWG\Get(
     *   path="/v2/community/post",
     *   summary="create Post message",
     *   tags={"V2.Group community"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(name="post_id", required=true, in="formData", type="string", description="mattermost channel id."),
     *  @SWG\Response(
     *       response=200,
     *       description="Success",
     *       @SWG\Schema(
     *            title="success",
     *            @SWG\Property(property="success", type="boolean"),
     *            @SWG\Property(property="dataVersion", type="string"),
     *            @SWG\Property(property="data", type="object",
     *               @SWG\Property(property="channel_id", type="string"),
     *               @SWG\Property(property="create_at", type="integer"),
     *               @SWG\Property(property="delete_at", type="integer"),
     *               @SWG\Property(property="edit_at", type="integer"),
     *               @SWG\Property(property="hashtags", type="string"),
     *               @SWG\Property(property="id", type="string"),
     *               @SWG\Property(property="is_pinned", type="boolean"),
     *               @SWG\Property(property="message", type="string"),
     *               @SWG\Property(property="metadata", type="object"),
     *               @SWG\Property(property="original_id", type="string"),
     *               @SWG\Property(property="parent_id", type="string"),
     *               @SWG\Property(property="pending_post_id", type="string"),
     *               @SWG\Property(property="reply_count", type="integer"),
     *               @SWG\Property(property="root_id", type="string"),
     *               @SWG\Property(property="type", type="string"),
     *               @SWG\Property(property="update_at", type="integer"),
     *               @SWG\Property(property="user_id", type="string"),
     *               @SWG\Property(property="props", type="object",
     *                   @SWG\Property(property="temp_id", type="string"),
     *                   @SWG\Property(property="user_id", type="string"),
     *                   @SWG\Property(property="user", type="object",
     *                     @SWG\Property(property="id", type="integer"),
     *                     @SWG\Property(property="avatar", type="string"),
     *                     @SWG\Property(property="is_vip", type="integer"),
     *                     @SWG\Property(property="online_setting", type="integer"),
     *                     @SWG\Property(property="sex", type="integer"),
     *                     @SWG\Property(property="user_type", type="integer"),
     *                     @SWG\Property(property="username", type="string"),
     *                   )
     *               ),
     *               @SWG\Property(property="user", type="object",
     *                 @SWG\Property(property="id", type="integer"),
     *                 @SWG\Property(property="avatar", type="string"),
     *                 @SWG\Property(property="is_vip", type="integer"),
     *                 @SWG\Property(property="online_setting", type="integer"),
     *                 @SWG\Property(property="sex", type="integer"),
     *                 @SWG\Property(property="user_type", type="integer"),
     *                 @SWG\Property(property="username", type="string"),
     *               )
     *          )
     *       )
     *   ),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     **/
    public function getPost(Request $request)
    {
        $request->validate([
            'post_id' => 'required',
        ]);

        try {
            $data = $this->communityService->getPost($request->post_id);
            return $this->sendResponse($data);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
