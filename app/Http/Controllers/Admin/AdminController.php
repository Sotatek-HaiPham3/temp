<?php

namespace App\Http\Controllers\Admin;

use App\Consts;
use App\Http\Controllers\AppBaseController;
use App\Http\Requests\CreateNewOrUpdateAdministrator;
use App\Http\Services\MasterdataService;
use App\Http\Services\TransactionService;
use App\Http\Services\UserService;
use App\Http\Services\AdminService;
use App\Http\Services\GameProfileService;
use App\Models\User;
use Mail;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\SendSms;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PharIo\Manifest\Email;
use App\Http\Requests\MailFormRequest;

class AdminController extends AppBaseController
{
    private $adminService;
    private $transactionService;
    private $userService;
    private $gameProfileService;

    public function __construct(AdminService $adminService, TransactionService $transactionService, UserService $userService)
    {
        $this->adminService = $adminService;
        $this->transactionService = $transactionService;
        $this->userService = $userService;
        $this->gameProfileService = new GameProfileService;
    }

    public function index()
    {
        $dataVersion = MasterdataService::getDataVersion();
        return view('admin.app')->with('dataVersion', $dataVersion);
    }

    public function clearCache()
    {
        MasterdataService::clearCacheAllTable();
        return $this->sendResponse('ok');
    }

    public function getUsers(Request $request)
    {
        $data = $this->adminService->getUsers($request->all());
        return $this->sendResponse($data);
    }

    public function getGamelancerForms(Request $request)
    {
        $data = $this->adminService->getGamelancerForms($request->all());
        return $this->sendResponse($data);
    }

    public function approveGamelancer(Request $request)
    {
        $request->validate([
            'id'        => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data   = $this->adminService->approveGamelancer($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function approveFreeGamelancer(Request $request)
    {
        $request->validate([
            'id'        => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data   = $this->adminService->approveFreeGamelancer($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function disapproveGamelancer(Request $request)
    {
        $request->validate([
            'id'        => 'required'
        ]);

        DB::beginTransaction();
        try {
            $data   = $this->adminService->disapproveGamelancer($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getInvitationCodes(Request $request)
    {
        $data = $this->adminService->getInvitationCodes($request->all());
        return $this->sendResponse($data);
    }

    public function getCurrentAdmin(Request $request)
    {
        try {
            $data   = $this->adminService->getCurrentAdmin();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function getAdministrators(Request $request)
    {
        try {
            $input  = $request->all();
            $data   = $this->adminService->getAdministrators($input);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function createNewOrUpdateAdministrator(CreateNewOrUpdateAdministrator $request)
    {
        DB::beginTransaction();
        try {
            $input  = $request->all();
            $data   = $this->adminService->createNewOrUpdateAdministrator($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getAdministratorById(Request $request)
    {
        try {
            $adminId    = $request->id;
            $data       = $this->adminService->getAdministratorById($adminId);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function deleteAdministrator(Request $request)
    {
        DB::beginTransaction();
        try {
            $adminId    = $request->id;
            $data       = $this->adminService->deleteAdministrator($adminId);
            DB::commit();
            return $this->sendResponse('Ok');
        } catch (\Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getUserTransactions(Request $request)
    {
        try {
            $input  = $request->all();
            $type = $request->type;
            $data   = $this->adminService->getUserTransactions($type, $input);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function updateUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateUser($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getUserBalances(Request $request) 
    {   
        try {
            $input  = $request->all();
            $data   = $this->adminService->getUserBalances($input);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function updateUserBalance(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->adminService->updateUserBalance($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateExcuteTransaction(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'is_approved' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $input = $request->all();
            $data  = $this->transactionService->updateExcuteTransaction($input);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getGamelancerInfoDetail(Request $request)
    {
        $request->validate([
            'id' => 'required'
        ]);

        $data  = $this->adminService->getGamelancerInfoDetail($request->id);
        return $this->sendResponse($data);
    }

    public function getReviews(Request $request)
    {
        $data  = $this->adminService->getReviews($request->all());
        return $this->sendResponse($data);
    }

    public function deleteReview(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:session_reviews,id'
        ]);

        DB::beginTransaction();
        try {
            $data  = $this->adminService->deleteReview($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function getUsernames(Request $request)
    {
        $data = $this->adminService->getUsernames($request->all());
        return $this->sendResponse($data);
    }

    public function getUserRestrictPricings(Request $request)
    {
        $data = $this->adminService->getUserRestrictPricings($request->all());
        return $this->sendResponse($data);
    }

    public function createUserRestrictPricing(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
            'min' => 'required|numeric|gte:1',
            'max' => 'required|numeric|gt:min'
        ]);

        DB::beginTransaction();
        try {
            $data  = $this->adminService->createUserRestrictPricing($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function updateUserRestrictPricing(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_restrict_pricings,id',
            'min' => 'required|numeric|gte:1',
            'max' => 'required|numeric|gt:min'
        ]);

        DB::beginTransaction();
        try {
            $data  = $this->adminService->updateUserRestrictPricing($request->all());
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }

    public function deleteUserRestrictPricing(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:user_restrict_pricings,id'
        ]);

        DB::beginTransaction();
        try {
            $data  = $this->adminService->deleteUserRestrictPricing($request->id);
            DB::commit();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollBack();
            throw $ex;
        }
    }
}
