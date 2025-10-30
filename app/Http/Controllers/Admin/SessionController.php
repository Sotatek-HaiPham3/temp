<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\AdminService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionController extends AppBaseController
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }
    
    public function getSessions(Request $request) {
        DB::beginTransaction();
        try {
            $data = $this->adminService->getSessions($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    public function getSessionDetail(Request $request) {
        DB::beginTransaction();
        try {
            $data = $this->adminService->getSessionDetail($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
