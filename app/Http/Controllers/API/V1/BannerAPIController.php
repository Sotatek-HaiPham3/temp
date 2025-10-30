<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\BannerService;
use App\Consts;
use DB;
use Auth;
use Exception;

class BannerAPIController extends AppBaseController
{
    protected $bannerService;

    public function __construct(BannerService $bannerService)
    {
        $this->bannerService = $bannerService;
    }

    /**
    * @SWG\Get(
    *   path="/v1/banners",
    *   summary="Get Banners",
    *   tags={"V1.Banners"},
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getBanners(Request $request)
    {
        $data = $this->bannerService->getBanners($request->all());
        return $this->sendResponse($data);
    }
}
