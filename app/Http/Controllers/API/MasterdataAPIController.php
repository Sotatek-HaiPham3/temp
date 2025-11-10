<?php

namespace App\Http\Controllers\API;

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
     *   path="/masterdata",
     *   summary="Get Masterdata",
     *   tags={"Masterdata"},
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
}
