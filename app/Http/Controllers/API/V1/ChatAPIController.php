<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AppBaseController;
use App\Http\Services\ChatService;
use App\Http\Requests\CreateChatMessageFormRequest;
use App\Consts;
use DB;
use Auth;
use Exception;

class ChatAPIController extends AppBaseController
{
    protected $chatService;

    public function __construct()
    {
        $this->chatService = new ChatService();
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/user-chat-session-list",
    *   summary="Get Session List",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUserChatSessionList(Request $request)
    {
        $data = $this->chatService->getUserChatSessionList($request->all());
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Post(
    *   path="/v1/chat/channels/direct",
    *   summary="Create direct channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="opposite_user_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function createDirectMessageChannel(Request $request)
    {
        $request->validate([
            'opposite_user_id' => 'required|exists:users,id'
        ]);

        DB::beginTransaction();
        try {
            $data = $this->chatService->createDirectMessageChannel($request->opposite_user_id);
            DB::commit();

            unset($data->mattermost_channel_id);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/chat/create-post",
    *   summary="Post message",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Property(
    *       type="array",
    *       property="images",
    *       @SWG\Items(type="string", description="Image path"),
    *   ),
    *   @SWG\Parameter(name="message", required=true, in="formData", type="string"),
    *   @SWG\Parameter(name="temp_id", required=true, in="formData", type="string", description="it's uuid4"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function createPost(CreateChatMessageFormRequest $request)
    {
        try {
            $data = $this->chatService->createPost($request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/posts-for-channel",
    *   summary="Get posts for channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="query", type="string"),
    *   @SWG\Parameter(name="prev_post_id", required=false, in="query", type="string", description="Last postt of current page."),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getPostsForChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->getPostsForChannel($request->channel_id, $request->all());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/chat/view-channel",
    *   summary="View channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function viewChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->viewChannel($request->channel_id);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Post(
    *   path="/v1/chat/search-channels",
    *   summary="Search channels",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="keyword", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function searchChannel(Request $request)
    {
        $request->validate([
            'keyword' => 'required'
        ]);

        try {
            $data = $this->chatService->searchChannel($request->keyword);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/channels-for-user",
    *   summary="Get channels for user",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getChannelsForUser(Request $request)
    {
        $params = $request->all();
        $data = $this->chatService->getChannelsForUser($params);
        return $this->sendResponse($data);
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/unread-messages",
    *   summary="Get unread messages",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getUnreadMessages(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:channels,mattermost_channel_id']);
            if ($validator->fails()) {
                return $this->sendResponse(null);
            }

            $data = $this->chatService->getUnreadMessages($request->channel_id);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/channel-detail",
    *   summary="Get channel detail",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getChannelById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['channel_id' => 'required|exists:channels,mattermost_channel_id']);
            if ($validator->fails()) {
                return $this->sendResponse(null);
            }

            $data = $this->chatService->getChannelById($request->channel_id, Auth::id());
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/detail-by-username",
    *   summary="Get channel detail by username",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="username", required=true, in="query", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=422, description="Data Invalid"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getChannelByUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users'
        ]);

        try {
            $data = $this->chatService->getChannelByUsername($request->username);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/chat/block-channel",
    *   summary="Block channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function blockChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->handleBlockChannel($request->channel_id, Consts::TRUE);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/chat/unblock-channel",
    *   summary="Unblock channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function unblockChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->handleBlockChannel($request->channel_id, Consts::FALSE);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/chat/mute-channel",
    *   summary="Mute channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function muteChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->handleMuteChannel($request->channel_id, Consts::TRUE);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/chat/unmute-channel",
    *   summary="Unmute channel",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function unmuteChannel(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->handleMuteChannel($request->channel_id, Consts::FALSE);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    private function validateChannel($request)
    {
        $request->validate([
            'channel_id' => 'required|exists:channels,mattermost_channel_id'
        ]);
    }

    /**
    * @SWG\Post(
    *   path="/v1/chat/system-logs",
    *   summary="Get System Logs",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Parameter(name="channel_id", required=true, in="formData", type="string"),
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getSystemLogs(Request $request)
    {
        $this->validateChannel($request);

        try {
            $data = $this->chatService->getSystemLogs($request->channel_id);
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Get(
    *   path="/v1/chat/token",
    *   summary="Get Mattermost token for user",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getMattermostToken(Request $request)
    {
        try {
            $data = $this->chatService->getMattermostToken();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

        /**
    * @SWG\Get(
    *   path="/v1/chat/total-channels-unread-message",
    *   summary="Get total channels unread message",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function getTotalChannelsUnreadMessage(Request $request)
    {
        try {
            $data = $this->chatService->getTotalChannelsUnreadMessage();
            return $this->sendResponse($data);
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    /**
    * @SWG\Put(
    *   path="/v1/chat/mark-as-view",
    *   summary="Mark as view chat",
    *   tags={"V1.Chat"},
    *   security={
    *     {"passport": {}},
    *   },
    *   @SWG\Response(response=200, description="Successful Operation"),
    *   @SWG\Response(response=401, description="Unauthenticated"),
    *   @SWG\Response(response=500, description="Internal Server Error")
    * )
    **/
    public function markAsView(Request $request)
    {
        DB::beginTransaction();
        try {
            $data = $this->chatService->markAsView();
            DB::commit();

            return $this->sendResponse($data);
        } catch (Exception $ex) {
            DB::rollback();
            throw $ex;
        }
    }
}
