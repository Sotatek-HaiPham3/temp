<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppBaseController;
use App\Http\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunityController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function getListRequestNameChange(Request $request)
    {
        $data = $this->adminService->getListCommunityRequestNameChange($request->all());
        return $this->sendResponse($data);
    }

    public function approveRequestNameChange(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->approveRejectRequestNameChange($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function rejectRequestNameChange(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->approveRejectRequestNameChange($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            throw $ex;
        }
    }

    public function getListGallery(Request $request)
    {
        $data = $this->adminService->getGallery($request->all());
        return $this->sendResponse($data);
    }

    public function createGallery(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->createGallery($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    public function updateGallery(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateGallery($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }

    }

    public function deleteGallery(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->deleteGallery($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
