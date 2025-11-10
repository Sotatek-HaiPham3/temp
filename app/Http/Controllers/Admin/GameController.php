<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\AdminService;
use App\Models\User;
use App\Utils;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\CreateGameRequest;
use App\Http\Requests\UpdateGameRequest;

class GameController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }
    
    public function getGames(Request $request) {
        DB::beginTransaction();
        try {
            $data = $this->adminService->getGames($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function editGame(Request $request)
    {
    	DB::beginTransaction();
        try {
            $data = $this->adminService->editGame($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function createGame(CreateGameRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createGame($request);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function updateGame(UpdateGameRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateGame($request);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function deleteGame(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteGame($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function orderGames(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->orderGames($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }
}
