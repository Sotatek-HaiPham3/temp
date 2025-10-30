<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\MasterdataService;
use App\Consts;
use App\Exceptions\Reports\TestException;
use Illuminate\Validation\ValidationException;

class MasterdataAPIController extends AppBaseController
{
    /**
     * @SWG\Get(
     *   path="/v1/masterdata",
     *   summary="Get Masterdata",
     *   tags={"V1.Masterdata"},
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getAll(Request $request)
    {
        $masterdata = MasterdataService::getAllData();
        return $this->sendResponse($masterdata);
    }

    /**
     * @SWG\Get(
     *   path="/v1/settings",
     *   summary="Get Settings",
     *   tags={"V1.Masterdata"},
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getSettings(Request $request)
    {
        $settings = MasterdataService::getOneTable("settings");
        return $this->sendResponse($settings);
    }
}
