<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\FirebaseService;
use Auth;
use DB;

class FirebaseAPIController extends AppBaseController {

    protected $fcmService;

    public function __construct()
    {
        $this->fcmService = new FirebaseService();
    }

    public function registerDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required',
            'token' => 'required',
        ]);

        $userId = Auth::id();
        $input = $request->all();
        $data = $this->fcmService->registerDevice($userId, $input);

        return $data;
    }

    public function deleteDevice(Request $request)
    {
        $request->validate([
            'device_id' => 'required',
        ]);

        $userId = Auth::id();
        $input = $request->all();
        $data = $this->fcmService->deleteDevice($userId, $input);

        return [];
    }

    public function pushNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'title' => 'required',
            'body' => 'required',
        ]);

        $params = $request->all();
        $userId = $request->user_id;
        $data = $this->fcmService->pushNotification($userId, $params);

        return [];
    }
}
