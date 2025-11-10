<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Services\MasterdataService;
use App\Http\Services\AdminService;
use App\Http\Requests\CreateRankRequest;
use App\Http\Requests\UpdateRankRequest;
use App\Http\Requests\CreateRewardRequest;
use App\Http\Requests\UpdateRewardRequest;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;



class SettingController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function getSiteSettings(Request $request)
    {
        try {
            $data = $this->adminService->getSiteSettings($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateSiteSettings(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateSiteSettings($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function getBanners(Request $request)
    {
        try {
            $data = $this->adminService->getBanners($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateBanner(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateBanner($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function createBanner(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createBanner($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function deleteBanner(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteBanner($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function getPlatforms(Request $request)
    {
        try {
            $data = $this->adminService->getPlatforms($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function getRankings(Request $request)
    {
        try {
            $data = $this->adminService->getRankings($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function createRanking(CreateRankRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createRanking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateRanking(UpdateRankRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateRanking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function deleteRanking(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteRanking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function getRewards(Request $request)
    {
        try {
            $data = $this->adminService->getRewards($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function createReward(CreateRewardRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createReward($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateReward(UpdateRewardRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateReward($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function deleteReward(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteReward($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function getTaskings(Request $request)
    {
        try {
            $data = $this->adminService->getTaskings($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function createTasking(CreateTaskRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createTasking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateTasking(UpdateTaskRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateTasking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function deleteTasking(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteTasking($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function getDailyCheckinPoints(Request $request)
    {
        try {
            $data = $this->adminService->getDailyCheckinPoints($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateDailyCheckinPoint(Request $request)
    {
        $request->validate([
            'exp' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->adminService->updateDailyCheckinPoint($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateMultipleDailyCheckinPoints(Request $request)
    {
        $request->validate([
            '*.exp' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->adminService->updateMultipleDailyCheckinPoints($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->sendError($ex->getMessage());
        }
    }

    public function getDailyCheckinPeriod(Request $request)
    {
        try {
            $data = $this->adminService->getDailyCheckinPeriod($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateDailyCheckinPeriod(Request $request)
    {
        $request->validate([
            'period' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->adminService->updateDailyCheckinPeriod($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            return $this->sendError($ex->getMessage());
        }
    }

}
