<?php

namespace App\Http\Controllers;

use App\Consts;
use App\Models\User;
use App\Utils;
use App\Http\Services\TransactionService;
use Illuminate\Http\Request;
use Auth;
use Cache;
use DB;
use Exception;
use Log;
use App\Events\TransactionUpdated;

class TransactionController extends AppBaseController
{
    private $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function depositPaypalWebHook(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->depositPaypalWebHook($request->all());
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            // $this->transactionService->saveErrorDetail($request->paymentId, $ex->getMessage());
        }
    }

    public function withdrawPaypalWebHook(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->withdrawPaypalWebHook($request->all());
            DB::commit();
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            // $this->transactionService->saveErrorDetail($request->paymentId, $ex->getMessage());
        }
    }

    public function paypalReturn(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->depositPaypalExecute($request->paymentId, $request->PayerID, $request->token);
            DB::commit();
            return redirect($request->redirectUrl);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);

            $transaction = $this->transactionService->saveErrorDetail($request->paymentId, $ex->getMessage());
            event(new TransactionUpdated($transaction));
        }
    }

    public function paypalCancel(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->transactionService->depositPaypalCancel($request->token);
            DB::commit();
            return redirect($request->redirectUrl);
        } catch (Exception $ex) {
            DB::rollBack();
            logger()->error($ex);
            // $this->transactionService->saveErrorDetail($request->paymentId, $ex->getMessage());
        }
    }

}
