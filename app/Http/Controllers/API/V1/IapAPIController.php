<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\IapService;
use App\Consts;
use DB;
use Auth;
use Exception;

class IapAPIController extends AppBaseController
{
    protected $iapService;

    public function __construct(IapService $iapService)
    {
        $this->iapService = $iapService;
    }

    /**
     * @SWG\Get(
     *   path="/v1/iap-items/ios",
     *   summary="Get Offers for iOS",
     *   tags={"V1.In-App Purchase"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getListItemIos(Request $request)
    {
        $data = $this->iapService->getItem(Consts::IAP_PLATFORM_IOS);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/v1/iap-items/android",
     *   summary="Get Offers for Android",
     *   tags={"V1.In-App Purchase"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getListItemAndroid(Request $request)
    {
        $data = $this->iapService->getItem(Consts::IAP_PLATFORM_ANDROID);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Post(
     *   path="/v1/iap-items/ios",
     *   summary="Purchase Item on iOS",
     *   tags={"V1.In-App Purchase"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="transaction_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="receipt_data",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function purchaseItemIos(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required',
            'receipt_data' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $transactionId = $request->transaction_id;
            $receiptData = $request->receipt_data;
            $data = $this->iapService->purchaseItemIos($userId, $transactionId, $receiptData);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    /**
     * @SWG\Post(
     *   path="/v1/iap-items/android",
     *   summary="Purchase Item on Android",
     *   tags={"V1.In-App Purchase"},
     *   security={
     *     {"passport": {}},
     *   },
     *  @SWG\Parameter(
     *       name="product_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="order_id",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *  @SWG\Parameter(
     *       name="token",
     *       in="formData",
     *       required=true,
     *       type="string"
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function purchaseItemAndroid(Request $request)
    {
        $request->validate([
            'product_id' => 'required',
            'order_id' => 'required',
            'token' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $params = $request->all();
            $data = $this->iapService->purchaseItemAndroid($userId, $params);
            DB::commit();
            return $this->sendResponse([]);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

}
