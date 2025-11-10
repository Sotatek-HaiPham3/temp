<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\AppBaseController;
use Auth;
use Exception;
use SystemNotification;

class SystemNotificationAPIController extends AppBaseController
{

    public function __construct() {}

    /**
     * @SWG\Get(
     *   path="/notifications",
     *   summary="Notification for a user",
     *   tags={"Notifications"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function getNotifications(Request $request)
    {
        $params = $request->all();
        $data = SystemNotification::getNotifiesByUserId(Auth::id(), $params);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Get(
     *   path="/notifications/total-unview",
     *   summary="Total unread notification for a user",
     *   tags={"Notifications"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function totalUnview(Request $request)
    {
        $data = SystemNotification::totalUnview(Auth::id());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Put(
     *   path="/notifications/mark-as-read",
     *   summary="Mark As Read Notification",
     *   tags={"Notifications"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(
     *       name="notify_ids",
     *       in="formData",
     *       required=true,
     *       type="array",
     *       items={
     *          {"type":"integer"}
     *       }
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function markAsRead(Request $request)
    {
        $request->validate([
            'notify_ids' => 'array'
        ]);

        $data = SystemNotification::markAsRead(Auth::id(), $request->notify_ids);
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Put(
     *   path="/notifications/mark-as-view",
     *   summary="Mark As View Notifications",
     *   tags={"Notifications"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function markAsView(Request $request)
    {
        $data = SystemNotification::markAsView(Auth::id());
        return $this->sendResponse($data);
    }

    /**
     * @SWG\Delete(
     *   path="/notifications/clear-all",
     *   summary="Delete Notification",
     *   tags={"Notifications"},
     *   security={
     *     {"passport": {}},
     *   },
     *   @SWG\Parameter(
     *       name="notify_ids",
     *       in="formData",
     *       required=true,
     *       type="array",
     *       items={
     *          {"type":"integer"}
     *       }
     *   ),
     *   @SWG\Response(response=200, description="Successful Operation"),
     *   @SWG\Response(response=401, description="Unauthenticated"),
     *   @SWG\Response(response=422, description="Data Invalid"),
     *   @SWG\Response(response=500, description="Internal Server Error")
     * )
     */
    public function deleteNotify(Request $request)
    {
        $data = SystemNotification::deleteNotification(Auth::id(), $request->input('filter'));
        return $this->sendResponse($data);
    }

}
