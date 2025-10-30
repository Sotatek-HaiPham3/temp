<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\AdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BountyController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }
    
    public function getBounties(Request $request) {
        DB::beginTransaction();
        try {
            $data = $this->adminService->getBounties($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function getBountyClaimRequest(Request $request) {
        DB::beginTransaction();
        try {
            $data = $this->adminService->getBountyClaimRequest($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
