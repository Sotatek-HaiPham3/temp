<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\NodebbService;
use App\Consts;
use DB;
use Auth;
use Exception;

class ForumAPIController extends AppBaseController
{
    protected $nodebbService;

    public function __construct()
    {
        $this->nodebbService = new NodebbService();
    }

    public function createTopic(Request $request)
    {
        $request->validate([
            'content' => 'required_without:imagePath',
            'imagePath' => 'required_without:content',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->createTopic($request->all());
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function deleteTopic(Request $request)
    {
        $request->validate([
            'tid' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->deleteTopic($request->tid);
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function createComment(Request $request)
    {
        $request->validate([
            'content' => 'required_without:imagePath',
            'imagePath' => 'required_without:content',
            'toPid' => 'required',
            'tid' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->createComment($request->tid, $request->username, $request->all());
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function upvote(Request $request)
    {
        $request->validate([
            'pid' => 'required',
            'tid' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->upvote($request->tid, $request->pid, $request->username);
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function downvote(Request $request)
    {
        $request->validate([
            'pid' => 'required',
            'tid' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->downvote($request->tid, $request->pid, $request->username);
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function unvote(Request $request)
    {
        $request->validate([
            'pid' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $data = $this->nodebbService->unvote($request->tid, $request->pid, $request->username);
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/forums/topics-for-user",
    *   summary="Get Topics List For User",
    *   tags={"Forums"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getTopicsForUser(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
        ]);

        // $data = $this->nodebbService->getTopicsForUser($request->all());
        return $this->sendResponse([]);
    }

    /**
    * @SWG\Get(
    *   path="/forums/posts-for-topics",
    *   summary="Get Posts List For Topics",
    *   tags={"Forums"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getPostsForTopic(Request $request)
    {
        $request->validate([
            'tid' => 'required',
        ]);

        $data = $this->nodebbService->getPostsForTopic($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/forums/sub-posts-for-post",
    *   summary="Get Sub Posts List For Post",
    *   tags={"Forums"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getSubPostsForPost(Request $request)
    {
        $request->validate([
            'tid' => 'required',
            'pid' => 'required',
        ]);

        $data = $this->nodebbService->getSubPostsForPost($request->all());
        return $this->sendResponse($data);
    }

    public function getPostsDetail(Request $request)
    {
        $data = $this->nodebbService->getPostsDetail($request->all());
        return $this->sendResponse($data);
    }
}
