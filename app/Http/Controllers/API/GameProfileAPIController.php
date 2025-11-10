<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\GameProfileService;
use App\Http\Requests\GameProfileRequest;
use App\Http\Requests\UpdateGameProfileRequest;
use App\Consts;
use DB;
use Auth;
use Exception;

class GameProfileAPIController extends AppBaseController
{
    protected $gameProfileService;

    public function __construct(GameProfileService $gameProfileService)
    {
        $this->gameProfileService = $gameProfileService;
    }

    /**
    * @SWG\Get(
    *   path="/game-profiles",
    *   summary="Get Game Profiles",
    *   tags={"Game Profiles"},
    *   @SWG\Parameter(
    *       name="slug",
    *       description="Slug of Game",
    *       in="query",
    *       type="string"
    *   ),
    *   @SWG\Parameter(
    *       name="search_key",
    *       in="query",
    *       type="string"
    *   ),
    *   @SWG\Parameter(
    *       name="type",
    *       in="query",
    *       type="string"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getAllGameProfiles(Request $request)
    {
        $data = $this->gameProfileService->getAllGameProfiles($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/featured-gamelancers",
    *   summary="Get Featured Gamelancers",
    *   tags={"Game Profiles"},
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getFeaturedGamelancers(Request $request)
    {
        $data = $this->gameProfileService->getFeaturedGamelancers($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/game-profiles",
    *   summary="Get My Game Profiles",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getMyGameProfiles(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = Auth::id();

        $data = $this->gameProfileService->getAllGameProfiles($params);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/game-profile/detail",
    *   summary="Get Game Profile Detail",
    *   tags={"Game Profiles"},
    *   @SWG\Parameter(name="username", in="query", required=true, type="string"),
    *   @SWG\Parameter(name="slug", in="query", required=true, type="string", description="Slug of Game"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getGameProfileDetail(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'slug' => 'required'
        ]);

        $params = $request->all();
        $params['limit_medias'] = 12;

        $data = $this->gameProfileService->getGameProfileDetail($params);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/user/game-profile/create",
    *   summary="Create Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="game_profile",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="game_id", description="Game ID"),
    *           @SWG\Property(type="string", property="title", description="Game Profile Title"),
    *           @SWG\Property(type="string", property="audio", description="Game Profile Audio"),
    *           @SWG\Property(
    *               type="array",
    *               property="offers",
    *               @SWG\Items(
    *                   @SWG\Property(property="type", type="string", enum={"hour","per_game"}, description="Type of offer"),
    *                   @SWG\Property(property="quantity", type="number", description="Quantity of hours or games"),
    *                   @SWG\Property(property="price", type="number", description="Total price")
    *               ),
    *           ),
    *           @SWG\Property(
    *               type="array",
    *               property="platform_ids",
    *               @SWG\Items(type="number", description="Platform ID"),
    *           ),
    *           @SWG\Property(
    *               type="array",
    *               property="medias",
    *               @SWG\Items(
    *                   @SWG\Property(property="type", type="string", enum={"image","video"}, description="Type of file"),
    *                   @SWG\Property(property="url", type="string", description="File url")
    *               ),
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=403, description="Permission Denied"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * ),
    **/
    public function createGameProfile(GameProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->gameProfileService->createGameProfile($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/user/game-profile/update",
    *   summary="Update Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="game_profile",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="id", description="Game Profile ID"),
    *           @SWG\Property(type="string", property="title", description="Game Profile Title"),
    *           @SWG\Property(type="string", property="audio", description="Game Profile Audio"),
    *           @SWG\Property(
    *               type="array",
    *               property="offers",
    *               @SWG\Items(
    *                   @SWG\Property(property="type", type="string", enum={"hour","per_game"}, description="Type of offer"),
    *                   @SWG\Property(property="quantity", type="number", description="Quantity of hours or games"),
    *                   @SWG\Property(property="price", type="number", description="Total price")
    *               ),
    *           ),
    *           @SWG\Property(
    *               type="array",
    *               property="platform_ids",
    *               @SWG\Items(type="number", description="Platform ID"),
    *           ),
    *           @SWG\Property(
    *               type="array",
    *               property="medias",
    *               @SWG\Items(
    *                   @SWG\Property(property="type",type="string",enum={"image","video"}, description="Type of file"),
    *                   @SWG\Property(property="url",type="string", description="File url")
    *               ),
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=403, description="Permission Denied"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * ),
    **/
    public function updateGameProfile(UpdateGameProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->gameProfileService->updateGameProfile($request->id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/user/game-profile/create-media",
    *   summary="Update Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="game_profile_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="type", in="formData", required=true, type="string", enum={"image","video"}),
    *   @SWG\Parameter(name="url", in="formData", required=true, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=403, description="Permission Denied"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * ),
    **/
    public function createGameProfileMedia(Request $request)
    {
        $mediaTypes = [Consts::GAME_PROFILE_MEDIA_TYPE_IMAGE, Consts::GAME_PROFILE_MEDIA_TYPE_VIDEO];
        $request->validate([
            'game_profile_id'   => 'required|belong_gamelancer',
            'type'              => [Rule::in($mediaTypes), 'required', 'string'],
            'url'               => 'required|url'
        ]);

        $data = $this->gameProfileService->createGameProfileMedia($request->game_profile_id, $request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Delete(
    *   path="/user/game-profile/delete-media",
    *   summary="Delete Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="game_profile_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Parameter(
    *       name="id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=403, description="Permission Denied"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function deleteGameProfileMedia(Request $request)
    {
        $request->validate([
            'game_profile_id'   => 'required|belong_gamelancer',
            'id'                => 'required'
        ]);

        $data = $this->gameProfileService->deleteGameProfileMedia($request->game_profile_id, $request->id);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Delete(
    *   path="/user/game-profile/delete",
    *   summary="Delete Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=403, description="Permission Denied"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function deleteGameProfile(Request $request)
    {
        // validate game profile able to delete
        $request->validate([
            'id' => 'required|belong_gamelancer'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->gameProfileService->deleteGameProfile($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/game-profile/reviews",
    *   summary="Get Game Profile Detail",
    *   tags={"Game Profiles"},
    *   @SWG\Parameter(name="game_profile_id", in="query", required=true, type="integer"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getGameProfileReviews(Request $request)
    {
        $request->validate([
            'game_profile_id' => 'required'
        ]);

        $data = $this->gameProfileService->getGameProfileReviews($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/game-profile/collection",
    *   summary="Get Game Profile Collection",
    *   tags={"Game Profiles"},
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getGameProfileCollection(Request $request)
    {
        $data = $this->gameProfileService->getGameProfileCollection();
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/user/game-profile/existed",
    *   summary="Get Already Game Profile",
    *   tags={"Game Profiles"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getExistedGameProfile(Request $request)
    {
        $data = $this->gameProfileService->getExistedGameProfile(Auth::id());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/game-statistics",
    *   summary="Get Game Statistics",
    *   tags={"Game Profiles"},
    *   security={},
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getGameStatistics(Request $request)
    {
        $data = $this->gameProfileService->getGameStatistics();
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/matching",
    *   summary="Quick Matching",
    *   tags={"Quick Matching"},
    *   @SWG\Parameter(name="slug", in="query", required=true, type="string"),
    *   @SWG\Parameter(name="sid", in="query", required=false, type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function quickMatching(Request $request)
    {
        $request->validate([
            'slug' => 'required'
        ]);

        $params = $request->all();
        $data = $this->gameProfileService->quickMatching($params);
        return $this->sendResponse($data);
    }
}
