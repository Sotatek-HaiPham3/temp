<?php

namespace App\Http\Controllers;

use App\Http\Services\MasterdataService;
use Illuminate\Pagination\LengthAwarePaginator;
use Response;

/**
 * @SWG\Swagger(
 *   basePath="/api",
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
        $result = $this->modifyResultIfNeed($result);

        $res = [
            'success' => true,
            'dataVersion' => MasterdataService::getDataVersion(),
            'data' => $result,
        ];

        return response()->json($res);
    }

    protected function modifyResultIfNeed($result)
    {
        if ($result instanceof LengthAwarePaginator) {
            $result = $result->toArray();
        }

        if (is_array($result) && array_key_exists('per_page', $result)) {
            $result['per_page'] = intval($result['per_page']);
        }

        return $result;
    }
}
