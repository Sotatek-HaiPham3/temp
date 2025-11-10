<?php

namespace App\Http\Controllers;

use App\Http\Services\MasterdataService;
use Response;

/**
 * @SWG\Swagger(
 *   basePath="/api/v1",
 *   @SWG\Info(
 *     title="Gamelancer APIs",
 *     version="1.0.0",
 *   )
 * )
 * This class should be parent class for other API controllers
 * Class AppBaseController
 */
class AppBaseController extends Controller
{
    public function sendResponse($result, $message = null)
    {
        $res = [
            'success' => true,
            'dataVersion' => MasterdataService::getDataVersion(),
            'data' => $result,
        ];

        return response()->json($res);
    }
}
