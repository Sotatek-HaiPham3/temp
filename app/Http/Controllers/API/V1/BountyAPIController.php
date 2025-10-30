<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\BountyService;
use App\Http\Requests\BountyRequest;
use App\Http\Requests\UpdateBountyRequest;
use App\Http\Requests\ClaimBountyRequest;
use App\Http\Requests\ClaimBountyActionRequest;
use App\Consts;
use DB;
use Auth;
use Exception;

class BountyAPIController extends AppBaseController
{
    protected $bountyService;

    public function __construct(BountyService $bountyService)
    {
        $this->bountyService = $bountyService;
    }

    /**
    * @SWG\Get(
    *   path="/v1/bounties",
    *   summary="Get Bounties",
    *   tags={"V1.Bounties"},
    *   @SWG\Parameter(
    *       name="slug",
    *       in="query",
    *       type="string"
    *   ),
    *   @SWG\Parameter(
    *       name="search_key",
    *       in="query",
    *       type="string"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getAllBounties(Request $request)
    {
        $data = $this->bountyService->getAllBounties($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/bounties",
    *   summary="Get My Bounties",
    *   tags={"V1.Bounties"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getMyBounties(Request $request)
    {
        $params = $request->all();
        $params['user_id'] = Auth::id();

        $data = $this->bountyService->getAllBounties($params);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/bounty/detail",
    *   summary="Get Bounty Detail",
    *   tags={"V1.Bounties"},
    *   @SWG\Parameter(
    *       name="slug",
    *       in="query",
    *       required=true,
    *       type="string"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getBountyDetail(Request $request)
    {
        $request->validate([
            'slug' => 'required'
        ]);

        $data = $this->bountyService->getBountyDetail($request->slug);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/create",
    *   summary="Create Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="game_id", description="Game ID"),
    *           @SWG\Property(type="string", property="title", description="Bounty Title"),
    *           @SWG\Property(type="string", property="description", description="Bounty Description"),
    *           @SWG\Property(type="string", property="media", description="Bounty Clip or Photo url"),
    *           @SWG\Property(type="number", property="price", description="Bounty Price"),
    *           @SWG\Property(
    *               type="array",
    *               property="platform_ids",
    *               @SWG\Items(type="number", description="Platform ID")
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * ),
    **/
    public function createBounty(BountyRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->bountyService->createBounty($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/user/bounty/update",
    *   summary="Update Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="id", description="Bounty ID"),
    *           @SWG\Property(type="string", property="title", description="Bounty Title"),
    *           @SWG\Property(type="string", property="description", description="Bounty Description"),
    *           @SWG\Property(type="string", property="slug", description="Bounty Slug"),
    *           @SWG\Property(type="string", property="media", description="Bounty Clip or Photo url"),
    *           @SWG\Property(type="number", property="price", description="Bounty Price"),
    *           @SWG\Property(
    *               type="array",
    *               property="platform_ids",
    *               @SWG\Items(type="number", description="Platform ID")
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * ),
    **/
    public function updateBounty(UpdateBountyRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->bountyService->updateBounty($request->id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Delete(
    *   path="/v1/user/bounty/delete",
    *   summary="Delete Bounty",
    *   tags={"V1.Bounties"},
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
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function deleteBounty(Request $request)
    {
        $request->validate([
            'id' => 'required|belong_user'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->deleteBounty($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/claim",
    *   summary="Claim Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Parameter(
    *       name="description",
    *       in="formData",
    *       required=true,
    *       type="string"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function claim(ClaimBountyRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->bountyService->claim($request->input('bounty_id'), $request->input('description'));
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/cancel-claim",
    *   summary="Cancel Claim Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="bounty_claim_request_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function cancelClaim(Request $request)
    {
        $request->validate([
            'bounty_claim_request_id'   => 'required|exists:bounty_claim_requests,id',
            'content'                   => $request->reason_id ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->cancelClaim($request->bounty_claim_request_id, $request->reason_id, $request->content);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/complete",
    *   summary="Complete Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function completeBounty(Request $request)
    {
        $request->validate([
            'bounty_id' => 'required|exists:bounties,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->completeBounty($request->bounty_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/mark-complete",
    *   summary="Mark Complete Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function markCompleteBounty(Request $request)
    {
        $request->validate([
            'bounty_id' => 'required|exists:bounties,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->markCompleteBounty($request->bounty_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/dispute",
    *   summary="Dispute Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Parameter(
    *       name="reason_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function disputeBounty(Request $request)
    {
        $request->validate([
            'bounty_id' => 'required|exists:bounties,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->disputeBounty($request->bounty_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/cancel-bounty",
    *   summary="Cancel Bounty From Gamelancer",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="bounty_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function cancelBountyFromGamelancer(Request $request)
    {
        $request->validate([
            'bounty_id' => 'required|exists:bounties,id',
            'content'   => $request->reason_id ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->cancelBountyFromGamelancer($request->bounty_id, $request->reason_id, $request->content);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/approve",
    *   summary="Approved Bounty Claim Request",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="bounty_claim_request_id",
    *       in="formData",
    *       required=true,
    *       type="integer"
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function approve(ClaimBountyActionRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->bountyService->approve($request->bounty_claim_request_id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/reject",
    *   summary="Rejected Bounty Claim Request",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(name="bounty_claim_request_id", in="formData", required=true, type="integer"),
    *   @SWG\Parameter(name="reason_id", in="formData", type="integer"),
    *   @SWG\Parameter(name="content", in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function reject(Request $request)
    {
        $request->validate([
            'bounty_claim_request_id'   => 'required|exists:bounty_claim_requests,id',
            'content'                   => $request->reason_id ? '' : 'required'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->reject($request->bounty_claim_request_id, $request->reason_id, $request->content);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/bounty/for-user",
    *   summary="Get Bounty Claim For User",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getBountyClaimForUser(Request $request)
    {
        $data = $this->bountyService->getBountyClaimForUser($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/user/bounty/for-gamelancer",
    *   summary="Get Bounty Claim For Gamelancer",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getBountyClaimForGamelancer(Request $request)
    {
        $data = $this->bountyService->getBountyClaimForGamelancer($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/v1/user/bounty/review",
    *   summary="Review Bounty",
    *   tags={"V1.Bounties"},
    *   security={
    *       {"passport": {}},
    *   },
    *   @SWG\Parameter(
    *       name="review",
    *       in="body",
    *       required=true,
    *       @SWG\Schema(
    *           @SWG\Property(type="integer", property="bounty_id"),
    *           @SWG\Property(type="number", property="rate"),
    *           @SWG\Property(type="string", property="description"),
    *           @SWG\Property(type="integer", property="recommend", enum={"image","video"}),
    *           @SWG\Property(
    *               type="array",
    *               property="tags",
    *               @SWG\Items(type="number", description="Review Tag ID"),
    *           ),
    *       )
    *   ),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function reviewBounty(Request $request)
    {
        $request->validate([
            'bounty_id'         => 'required|exists:bounties,id',
            'rate'              => 'required|between:1,5',
            'description'       => 'required|max:500',
            'recommend'         => [Rule::in([Consts::TRUE, Consts::FALSE]), 'nullable'],
            'tags'              => 'array'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->bountyService->reviewBounty($request->bounty_id, $request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
