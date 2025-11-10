<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Services\TransactionService;

class WebHookStripeAPIController extends Controller
{
    private $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function depositWebHook(Request $request)
    {
        $data = $this->transactionService->depositStripeWebHook($request->all());
    }

    public function withdrawWebHook(Request $request)
    {
        $data = $this->transactionService->withdrawStripeWebHook($request->all());
    }
}
