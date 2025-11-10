<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\SocicalNetworkRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Services\MasterdataService;
use App\Http\Services\AdminService;

class SiteSettingController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function getSocialNetworks()
    {
        try {
            $data = $this->adminService->getSocialNetworks();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function addSocialNetwork(SocicalNetworkRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->addSocialNetwork($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function updateSocialNetWork(SocicalNetworkRequest $request)
    {
        DB::beginTransaction();
        try {
            $socialNetworkId = $request->input('id');
            $input = $request->except('id');

            $data = $this->adminService->updateSocialNetWork($socialNetworkId, $input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }

    public function removeSocialNetwork($socialNetworkId)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->removeSocialNetwork($socialNetworkId);
            DB::commit();
            return $this->sendResponse('Ok');
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            return $this->sendError($ex->getMessage());
        }
    }
}
