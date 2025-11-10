<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\CurrencyExchange;
use App\Utils\TimeUtils;
use App\Models\GameProfile;
use App\Models\GameProfileOffer;
use App\Models\Session;
use App\Models\SessionAddingRequest;
use App\Models\Setting;
use App\Models\User;
use App\Models\SessionReview;
use App\Models\SessionSystemMessage;
use App\Models\GamelancerAvailableTime;
use App\Models\UserSetting;
use App\Models\Reason;
use App\Models\Channel;
use App\Models\Tip;
use App\Models\SessionReason;
use App\Http\Services\UserService;
use App\Http\Services\ChatService;
use App\Exceptions\Reports\InvalidActionException;
use App\Exceptions\Reports\MaxGameQuantityException;
use App\Exceptions\Reports\NotEnoughBalancesException;
use App\Exceptions\Reports\ReviewSessionException;
use App\Exceptions\Reports\InvalidSessionScheduleException;
use App\Exceptions\Reports\OnlyBookableUserOnlineException;
use App\Exceptions\Reports\InvalidSessionNowException;
use App\Exceptions\Reports\SessionAlreadyReviewException;
use App\Exceptions\Reports\GamelancerOfflineException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Events\SessionTabUpdated;
use App\Events\SessionSystemMessageUpdated;
use App\Events\SessionBooked;
use Carbon\Carbon;
use Auth;
use Mail;
use App\Mails\SessionBookedMail;
use App\Mails\SessionAcceptedMail;
use App\Mails\SessionRejectedMail;
use App\Mails\SessionStartingMail;
use App\Mails\SessionReviewMail;
use App\Mails\BookingSessionWhenGamelancerOfflineMail;
use App\Jobs\CheckSessionResponseInvitation;
use App\Jobs\ProcessSessionCheckReady;
use App\Jobs\ProcessSessionCompleted;
use App\Jobs\CheckSessionScheduleExpiredTime;
use App\Jobs\CalculateUserRating;
use App\Jobs\SendSmsNotificationJob;
use App\Jobs\CalculateGameStatistic;
use App\Jobs\CalculateGameProfileStatistic;
use App\Jobs\CollectTaskingJob;
use Exception;
use App\Traits\SessionTrait;
use App\Traits\NotificationTrait;
use Aws;
use App\Utils\UserOnlineUtils;
use App\Models\Tasking;

class SessionService extends BaseService {

    use SessionTrait, NotificationTrait;

    private $userService;

    public function __construct()
    {
        $this->chatService = new ChatService();
        $this->userService = new UserService();
        $this->transactionService = new TransactionService();
    }

    public function bookGameProfile($params)
    {
        $gameProfileId = array_get($params, 'game_profile_id');
        $gameProfile = GameProfile::where('id', $gameProfileId)
            ->where('is_active', Consts::TRUE)
            ->first();

        $this->validateBookSession($gameProfile, $params);

        $channel = $this->chatService->createDirectMessageChannel($gameProfile->user_id);

        if ($this->isFreeGameProfile($gameProfileId)) {
            return $this->bookFreeGameProfile($gameProfile, $channel->id);
        }

        return $this->bookPaidGameProfile($gameProfile, $params, $channel->id);
    }

    private function bookFreeGameProfile($gameProfile, $channelId)
    {
        $now = Utils::currentMilliseconds();
        $this->validateOverlapSessionSchedule(
            $gameProfile->user_id,
            $now,
            1, // free session default 1 hour
            ['exceptions_key' => 'exceptions.invalid_session_schedule_user']
        );

        $session = $this->createSession([
            'now'            => Utils::currentMilliseconds(),
            'channelId'      => $channelId,
            'claimerId'      => Auth::id(),
            'gameProfile'    => $gameProfile
        ]);

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_BOOK_FREE);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Book session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_BOOK_FREE);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_BOOK_FREE);

        $this->fireBookSessionSuccess($session);

        return $session;
    }

    private function bookPaidGameProfile($gameProfile, $params, $channelId)
    {
        // Default quantity = 1 if book type is per_game
        if (array_get($params, 'type') === Consts::GAME_TYPE_PER_GAME) {
            $params['quantity'] = 1; // ~ 1 hour
        }

        $claimerId = Auth::id();
        $quantity = array_get($params, 'quantity');
        $timeoffset = array_get($params, 'timeoffset');
        $schedule = array_get($params, 'schedule');

        // check overlap session schedule.
        $now = Utils::currentMilliseconds();
        $realSchedule = $schedule ? TimeUtils::clientToUtc($schedule, $timeoffset)->timestamp * 1000 : $now;
        $this->validateOverlapSessionSchedule(
            $gameProfile->user_id,
            $realSchedule,
            $quantity,
            ['exceptions_key' => 'exceptions.invalid_session_schedule_user']
        );

        // check enough balances
        $offer = $this->getOfferByQuantity($gameProfile->id, $params);
        if (!$offer) {
            throw new InvalidActionException('exceptions.invalid_game_profile_price');
        }
        $escrowBalances = $this->calculatePriceByOffer($offer, $quantity);
        $this->escrowSession($claimerId, $escrowBalances);

        $session = $this->createSession([
            'now'               => $now,
            'channelId'         => $channelId,
            'claimerId'         => $claimerId,
            'gameProfile'       => $gameProfile,
            'offer_id'          => $offer->id,
            'schedule_at'       => $realSchedule,
            'quantity'          => $quantity,
            'escrow_balance'    => $escrowBalances
        ]);

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_BOOK_PAID);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Book session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_BOOK_PAID);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_BOOK_PAID);

        $this->fireBookSessionSuccess($session);

        return $session;
    }

    private function createSession($params)
    {
        $now         = array_get($params, 'now');
        $channelId   = array_get($params, 'channelId');
        $claimerId   = array_get($params, 'claimerId');
        $gameProfile = array_get($params, 'gameProfile');

        $session = Session::create([
            'gamelancer_id'     => $gameProfile->user_id,
            'claimer_id'        => $claimerId,
            'game_profile_id'   => $gameProfile->id,
            'channel_id'        => $channelId,
            'type'              => Consts::SESSION_TYPE_FREE,
            'booked_at'         => $now,
            'schedule_at'       => $now,
            'fee'               => Setting::getValue(Consts::SESSION_FEE_KEY),
            'status'            => Consts::SESSION_STATUS_BOOKED
        ]);

        if (!empty($params['offer_id'])) {
            $session->offer_id = $params['offer_id'];
        }

        if (!empty($params['schedule_at'])) {
            $session->schedule_at = $params['schedule_at'];
            $session->type = $params['schedule_at'] === $now ? Consts::SESSION_TYPE_NOW : Consts::SESSION_TYPE_SCHEDULE;
        }

        if (!empty($params['quantity'])) {
            $session->quantity = $params['quantity'];
        }

        if (!empty($params['escrow_balance'])) {
            $session->escrow_balance = $params['escrow_balance'];
        }

        $session->save();

        return $session;
    }

    public function cancelBooking($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->where('claimer_id', Auth::id())
            ->whereIn('status', [Consts::SESSION_STATUS_BOOKED, Consts::SESSION_STATUS_ACCEPTED])
            ->first();

        $this->validateSession($session);

        if ($this->isFreeSession($session)) {
            return $this->cancelBookFreeSession($session);
        }
        return $this->cancelBookPaidSession($session);
    }

    private function cancelBookFreeSession($session)
    {
        $session->status = Consts::SESSION_STATUS_CANCELED;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_CANCEL_FREE);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Cancel book session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_CANCEL_FREE);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_CANCEL_FREE);

        return $session;
    }

    private function cancelBookPaidSession($session)
    {
        $this->userService->addMoreBalance($session->claimer_id, $session->escrow_balance);

        $session->escrow_balance = null;
        $session->status = Consts::SESSION_STATUS_CANCELED;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_CANCEL_PAID);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Cancel book session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_CANCEL_PAID);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_CANCEL_PAID);

        return $session;
    }

    public function rejectBookingGameProfile($sessionId, $params, $autoAction = Consts::FALSE)
    {
        $session = Session::where('id', $sessionId)
            ->whereIn('status', [Consts::SESSION_STATUS_BOOKED, Consts::SESSION_STATUS_ACCEPTED])
            ->first();

        $this->validateSession($session);

        if ($this->isFreeSession($session)) {
            return $this->rejectBookFreeSession($session, $autoAction);
        }
        return $this->rejectBookPaidSession($session, $params, $autoAction);
    }

    private function rejectBookFreeSession($session, $autoAction)
    {
        $session->status = Consts::SESSION_STATUS_REJECTED;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_REJECT_FREE);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Rejected session');

        $notifyParams = ['auto_action' => $autoAction];
        $this->createUserNotification($session, Consts::SESSION_ACTION_REJECT_FREE, $notifyParams);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_REJECT_FREE, $notifyParams);

        return $session;
    }

    private function rejectBookPaidSession($session, $params, $autoAction)
    {
        $reasonId = array_get($params, 'reason_id');
        $reasonContent = array_get($params, 'content');
        $reason = $this->getSessionReason($reasonId, $reasonContent, Consts::REASON_TYPE_CANCEL);

        $escrowBalances = $session->escrow_balance;
        $this->userService->addMoreBalance($session->claimer_id, $session->escrow_balance);

        $session->escrow_balance = null;
        $session->status = Consts::SESSION_STATUS_REJECTED;
        $session->reason_id = $reason->id;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_REJECT_PAID);

        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Rejected session');

        $notifyParams = [
            'auto_action' => $autoAction,
            'escrow_balance' => $escrowBalances,
            'reason' => $reason->content
        ];
        $this->createUserNotification($session, Consts::SESSION_ACTION_REJECT_PAID, $notifyParams);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_REJECT_PAID, $notifyParams);

        return $session;
    }

    public function acceptBookingGameProfile($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->where('status', Consts::SESSION_STATUS_BOOKED)
            ->first();

        $this->validateSession($session);

        $isFreeSession = $this->isFreeSession($session);
        $now = Utils::currentMilliseconds();
        $this->validateOverlapSessionSchedule(
            $session->gamelancer_id,
            $isFreeSession ? $now : $session->schedule_at,
            $isFreeSession ? 1 : $session->quantity,
            ['exceptions_key' => 'exceptions.invalid_session_schedule_gamelancer']
        );

        if ($isFreeSession) {
            return $this->acceptBookFreeSession($session, $now);
        }
        return $this->acceptBookPaidSession($session);
    }

    private function acceptBookFreeSession($session, $now)
    {
        if ($this->hasPlayingSession($session->gamelancer_id)) {
            throw new InvalidSessionNowException('exceptions.invalid_session_now.gamelancer');
        }

        $session->gamelancer_ready = Consts::TRUE;
        $session->status = Consts::SESSION_STATUS_STARTING;
        $session->start_at = $now;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_ACCEPT_FREE);

        $this->fireSessionTabUpdated($session);
        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Accepted session');

        $this->createUserNotification($session, Consts::SESSION_ACTION_ACCEPT_FREE);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_ACCEPT_FREE);

        return $session;
    }

    private function acceptBookPaidSession($session)
    {
        $isBookNowSession = $session->type === Consts::SESSION_TYPE_NOW;
        $isPlaying = $this->hasPlayingSession($session->gamelancer_id);
        if ($isPlaying && $isBookNowSession) {
            throw new InvalidSessionNowException('exceptions.invalid_session_now.gamelancer');
        }

        $session->status = Consts::SESSION_STATUS_ACCEPTED;
        if ($isBookNowSession) {
            $session->gamelancer_ready = Consts::TRUE;
            $session->status = Consts::SESSION_STATUS_STARTING;
            $session->start_at = Utils::currentMilliseconds();
        }
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_ACCEPT_PAID);

        $this->fireSessionTabUpdated($session);
        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Accepted session');

        $this->createUserNotification($session, Consts::SESSION_ACTION_ACCEPT_PAID);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_ACCEPT_PAID);

        return $session;
    }

    public function readySession($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->where('status', Consts::SESSION_STATUS_STARTING)
            ->first();

        $this->validateSession($session);

        $isGamelancer = Auth::id() === $session->gamelancer_id;
        if ($isGamelancer) {
            $session->gamelancer_ready = Consts::TRUE;
        } else {
            $session->claimer_ready = Consts::TRUE;
        }

        if ($session->gamelancer_ready && $session->claimer_ready) {
            $session->status = Consts::SESSION_STATUS_RUNNING;
            $session->ready_at = Utils::currentMilliseconds();
        }

        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_READY);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Ready session');

        $this->createSessionMessage($session, Consts::SESSION_ACTION_READY);

        return $session;
    }

    public function addSessionRequest($sessionId, $quantityMinutes)
    {
        $session = Session::where('id', $sessionId)->where('claimer_id', Auth::id())->first();

        $this->validateSession($session);

        $offer = GameProfileOffer::withTrashed()
            ->where('id', $session->offer_id)
            ->where('type', Consts::GAME_TYPE_HOUR)
            ->first();

        $this->validateSession($offer);

        $request = SessionAddingRequest::where('session_id', $sessionId)
            ->where('status', Consts::SESSION_ADDING_REQUEST_STATUS_PENDING)
            ->exists();
        if ($request) {
            throw new InvalidActionException('exceptions.already_add_time');
        }

        // check overlap session schedule
        $quantityHours = BigNumber::new($quantityMinutes)->div(60, BigNumber::ROUND_MODE_FLOOR)->toString();
        $totalQuantity = BigNumber::new($session->quantity)->add($quantityHours)->toString();
        $this->validateOverlapSessionSchedule(
            $session->gamelancer_id,
            $session->schedule_at,
            $totalQuantity,
            ['type' => Consts::SESSION_ACTION_ADDTIME, 'exceptions_key' => 'exceptions.invalid_session_schedule_user']
        );

        $escrowBalances = $this->calculatePriceByOffer($offer, $quantityHours);
        $this->escrowSession($session->claimer_id, $escrowBalances);

        $addingRequest = SessionAddingRequest::create([
            'session_id'        => $sessionId,
            'quantity'          => $quantityHours,
            'escrow_balance'    => $escrowBalances,
            'status'            => Consts::SESSION_ADDING_REQUEST_STATUS_PENDING
        ]);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Add more session (games or hours)');

        $messageParams = [
            'quantity' => $quantityMinutes,
            'escrow_balance' => $escrowBalances
        ];
        $this->createSessionMessage($session, Consts::SESSION_ACTION_ADDTIME, $messageParams);

        return $addingRequest;
    }

    public function rejectAddingRequest($requestId)
    {
        $addingRequest = SessionAddingRequest::where('id', $requestId)
            ->where('status', Consts::SESSION_ADDING_REQUEST_STATUS_PENDING)
            ->first();

        $this->validateSession($addingRequest);

        $session = Session::where('id', $addingRequest->session_id)
            ->where('gamelancer_id', Auth::id())
            ->first();

        $this->validateSession($session);

        $escrowBalances = $addingRequest->escrow_balance;
        $this->userService->addMoreBalance($session->claimer_id, $addingRequest->escrow_balance);

        $addingRequest->escrow_balance = null;
        $addingRequest->status = Consts::SESSION_ADDING_REQUEST_STATUS_REJECTED;
        $addingRequest->save();

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Rejected add more session');

        $messageParams = [
            'quantity' => BigNumber::new($addingRequest->quantity)->mul(60)->toString(), //hours to minutes
            'escrow_balance' => $escrowBalances
        ];
        $this->createSessionMessage($session, Consts::SESSION_ACTION_REJECT_ADDTIME, $messageParams);

        return $addingRequest;
    }

    public function acceptAddingRequest($requestId)
    {
        $addingRequest = SessionAddingRequest::where('id', $requestId)
            ->where('status', Consts::SESSION_ADDING_REQUEST_STATUS_PENDING)
            ->first();

        $this->validateSession($addingRequest);

        $session = Session::where('id', $addingRequest->session_id)
            ->where('gamelancer_id', Auth::id())
            ->first();

        $this->validateSession($session);

        $offer = GameProfileOffer::withTrashed()->find($session->offer_id);
        $this->validateSession($offer);

        // check overlap session schedule.
        $totalQuantity = BigNumber::new($session->quantity)->add(BigNumber::new($addingRequest->quantity))->toString();
        $this->validateOverlapSessionSchedule(
            $session->gamelancer_id,
            $session->schedule_at,
            $totalQuantity,
            ['type' => Consts::SESSION_ACTION_ADDTIME, 'exceptions_key' => 'exceptions.invalid_session_schedule_gamelancer']
        );

        $session->quantity = $totalQuantity;
        $session->escrow_balance = BigNumber::new($session->escrow_balance)->add(BigNumber::new($addingRequest->escrow_balance))->toString();
        $session->save();

        $addingRequest->status = Consts::SESSION_ADDING_REQUEST_STATUS_APPROVED;
        $addingRequest->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_ACCEPT_ADDTIME);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Accepted add more session');

        $messageParams = [
            'quantity' => BigNumber::new($addingRequest->quantity)->mul(60)->toString(), //hours to minutes
            'escrow_balance' => $addingRequest->escrow_balance
        ];
        $this->createSessionMessage($session, Consts::SESSION_ACTION_ACCEPT_ADDTIME, $messageParams);

        return $addingRequest;
    }

    public function markAsComplete($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->where('gamelancer_id', Auth::id())
            ->where('status', Consts::SESSION_STATUS_RUNNING)
            ->first();

        $this->validateSession($session);

        $offer = GameProfileOffer::where('id', $session->offer_id)
            ->where('type', Consts::GAME_TYPE_PER_GAME)
            ->first();

        $this->validateSession($offer);

        $session->status = Consts::SESSION_STATUS_MARK_COMPLETED;
        $session->save();

        $this->fireSessionPlayingUpdated($session);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_MARK_COMPLETE);

        return $session;
    }

    public function rejectMarkComplete($sessionId, $params)
    {
        $session = Session::where('id', $sessionId)
            ->where('claimer_id', Auth::id())
            ->where('status', Consts::SESSION_STATUS_MARK_COMPLETED)
            ->first();

        $this->validateSession($session);

        $reasonId = array_get($params, 'reason_id');
        $reasonContent = array_get($params, 'content');
        $reason = $this->getSessionReason($reasonId, $reasonContent, Consts::REASON_TYPE_DECLINE);

        SessionReason::create([
            'session_id' => $session->id,
            'reason_id'  => $reason->id
        ]);

        $session->status = Consts::SESSION_STATUS_RUNNING;
        $session->save();

        $this->fireSessionPlayingUpdated($session);

        $messageParams = ['reason' => $reason->content];
        $this->createSessionMessage($session, Consts::SESSION_ACTION_REJECT_COMPLETE, $messageParams);

        return $session;
    }

    public function continueSession($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->where('gamelancer_id', Auth::id())
            ->where('status', Consts::SESSION_STATUS_RUNNING)
            ->first();

        $this->validateSession($session);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_CONTINUE);

        return $session;
    }

    public function stopSession($sessionId, $params)
    {
        $session = Session::where('id', $sessionId)
            ->where('status', Consts::SESSION_STATUS_RUNNING)
            ->first();

        $this->validateSession($session);

        $offer = GameProfileOffer::withTrashed()
            ->where('id', $session->offer_id)
            ->first();

        $this->validateSession($offer);

        $isGameSession = $offer->type !== Consts::GAME_TYPE_HOUR;

        $session->end_at = Utils::currentMilliseconds();
        $session->quantity_played = $isGameSession ? 0 : $this->calculateRealHoursPlayed($session);

        $isGamelancer = Auth::id() === $session->gamelancer_id;
        $paidBalance = $isGamelancer ? $this->calculatePriceByOffer($offer, $session->quantity_played) : $session->escrow_balance;

        if ($isGamelancer) {
            // refund escrow
            $this->userService->addMoreBalance($session->claimer_id, $session->escrow_balance);
            $this->userService->subtractBalance($session->claimer_id, $paidBalance);

            $session->gamelancer_stop = Consts::TRUE;
            $session->escrow_balance = null;
        } else {
            $session->claimer_stop = Consts::TRUE;
        }
        $actuallyReceived = $this->payWage($session->gamelancer_id, $paidBalance, $session->fee);

        $reasonId = array_get($params, 'reason_id');
        $reasonContent = array_get($params, 'content');
        $reason = $this->getSessionReason($reasonId, $reasonContent, Consts::REASON_TYPE_CANCEL);

        $session->status = Consts::SESSION_STATUS_STOPPED;
        $session->reason_id = $reason->id;
        $session->save();

        $this->createSessionTransaction($session, $actuallyReceived, $paidBalance);

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_STOP);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Stop session');

        $notifyParams = [
            'rewards' => $actuallyReceived,
            'coins' => $paidBalance
        ];
        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_STOP, $notifyParams);
        $this->createUserNotification($session, Consts::SESSION_ACTION_STOP, $notifyParams);

        $this->calculateStatistic($session, Consts::SESSION_ACTION_STOP);

        $messageParams = [
            'reason' => $reason->content
        ];
        $this->createSessionMessage($session, Consts::SESSION_ACTION_STOP, $messageParams);

        return $session;
    }

    public function completeSession($sessionId)
    {
        $session = Session::where('id', $sessionId)
            ->whereIn('status', [Consts::SESSION_STATUS_RUNNING, Consts::SESSION_STATUS_MARK_COMPLETED])
            ->first();

        $this->validateSession($session);

        if ($this->isFreeSession($session)) {
            return $this->completeFreeSession($session);
        }
        return $this->completePaidSession($session);
    }

    private function completeFreeSession($session)
    {
        $session->status = Consts::SESSION_STATUS_COMPLETED;
        $session->end_at = Utils::currentMilliseconds();
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_COMPLETE_FREE);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Completed session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_COMPLETE_FREE);
        $this->createUserNotification($session, Consts::SESSION_ACTION_COMPLETE_FREE);

        $this->calculateStatistic($session, Consts::SESSION_ACTION_COMPLETE_FREE);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_COMPLETE_FREE);

        $this->collectUserTasking($session, Tasking::PLAY_FREE_SESSION);

        return $session;
    }

    private function completePaidSession($session)
    {
        $offer = GameProfileOffer::withTrashed()->find($session->offer_id);
        $this->validateSession($offer);

        // paid cost
        $paidBalance = $this->calculatePriceByOffer($offer, $session->quantity);
        $actuallyReceived = $this->payWage($session->gamelancer_id, $paidBalance, $session->fee);
        $this->userService->addMoreBalance($session->claimer_id, $session->escrow_balance);
        $this->userService->subtractBalance($session->claimer_id, $paidBalance);

        $session->quantity_played = $session->quantity;
        $session->escrow_balance = null;
        $session->status = Consts::SESSION_STATUS_COMPLETED;
        $session->end_at = Utils::currentMilliseconds();
        $session->save();

        $this->createSessionTransaction($session, $actuallyReceived, $paidBalance);

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_COMPLETE_PAID);

        $this->fireSessionPlayingUpdated($session);

        $this->performFirehosePut($session, 'Completed session');

        $sumTips = Tip::where('object_id', $session->id)
            ->where('type', Consts::OBJECT_TYPE_SESSION)
            ->sum('tip');
        $notifyParams = [
            'rewards' => $actuallyReceived,
            'coins' => $paidBalance,
            'tip' => $sumTips
        ];
        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_COMPLETE_PAID, $notifyParams);
        $this->createUserNotification($session, Consts::SESSION_ACTION_COMPLETE_PAID, $notifyParams);

        $this->calculateStatistic($session, Consts::SESSION_ACTION_COMPLETE_PAID);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_COMPLETE_PAID, $notifyParams);

        $this->collectUserTasking($session, Tasking::PLAY_FREE_SESSION);

        return $session;
    }

    public function restartSession($sessionId, $params)
    {
        $session = Session::where('id', $sessionId)
            ->where('status', Consts::SESSION_STATUS_COMPLETED)
            ->first();

        $this->validateSession($session);


        $session->has_restart = Consts::TRUE;
        $session->save();

        $gameProfile = GameProfile::where('id', $session->game_profile_id)
            ->where('is_active', Consts::TRUE)
            ->first();
        $this->validateBookSession($gameProfile, $params);

        $isFreeGameProfile = $this->isFreeGameProfile($session->game_profile_id);
        if ($isFreeGameProfile) {
            return $this->bookFreeGameProfile($gameProfile, $session->channel_id);
        }

        $offer = GameProfileOffer::withTrashed()
            ->where('id', $session->offer_id)
            ->first();

        $this->validateSession($offer);

        $addedQuantity = SessionAddingRequest::where('session_id', $sessionId)
            ->where('status', Consts::SESSION_ADDING_REQUEST_STATUS_APPROVED)
            ->sum('quantity');

        $originQuantity = BigNumber::new($session->quantity)->sub($addedQuantity);
        $data = [
            'type' => $offer->type,
            'quantity' => $originQuantity->isNegative() ? 1 : $originQuantity->toString(), // just make sure that the quantity isn't negative
            'game_profile_id' => $session->game_profile_id
        ];
        $params = array_merge($params, $data);

        return $this->bookPaidGameProfile($gameProfile, $params, $session->channel_id);
    }

    public function reviewSession($sessionId, $params)
    {
        $status = [
            Consts::SESSION_STATUS_COMPLETED,
            Consts::SESSION_STATUS_STOPPED
        ];
        $session = Session::where('id', $sessionId)
            ->whereIn('status', $status)
            ->first();

        $this->validateSession($session);

        if ($session->claimer_id !== Auth::id()) {
            throw new InvalidActionException();
        }

        $sessionReviewExists = SessionReview::withTrashed()->where('object_id', $sessionId)
            ->where('object_type', Consts::OBJECT_TYPE_SESSION)
            ->where('reviewer_id', Auth::id())
            ->exists();

        if ($sessionReviewExists) {
            throw new SessionAlreadyReviewException();
        }

        $recommend = array_get($params, 'recommend');
        $review = SessionReview::create([
            'object_id' => $sessionId,
            'game_profile_id' => $session->gameProfile->id,
            'reviewer_id' => $session->claimer_id,
            'user_id' => $session->gamelancer_id,
            'object_type' => Consts::OBJECT_TYPE_SESSION,
            'rate' => array_get($params, 'rate'),
            'description' => array_get($params, 'description'),
            'recommend' => array_get($params, 'recommend'),
            'submit_at' => Utils::currentMilliseconds()
        ]);

        if ($recommend) {
            $this->createSessionReviewTags($review, array_get($params, 'tags', []));
        }

        $session->claimer_has_review = Consts::TRUE;
        $session->save();

        $tip = array_get($params, 'tip');
        $notifyParams = [
            'star' => $review->rate,
            'review' => $review,
            'tip' => $tip
        ];
        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_REVIEW, $notifyParams);

        $this->calculateStatistic($session, Consts::SESSION_ACTION_REVIEW);

        if ($tip) {
            $params['receiver_id'] = $session->gamelancer_id;
            $this->transactionService->tip($params, Consts::TIP_VIA_REVIEW);
        }

        $this->createSessionMessage($session, Consts::SESSION_ACTION_REVIEW, $notifyParams);

        return $review;
    }

    private function validateBookSession($gameProfile, $params)
    {
        $this->validateSession($gameProfile, 'exceptions.game_profile_not_existed');

        $gameprofileOwnerUserType = User::where('id', $gameProfile->user_id)->value('user_type');
        if (!in_array($gameprofileOwnerUserType, [Consts::USER_TYPE_PREMIUM_GAMELANCER, Consts::USER_TYPE_FREE_GAMELANCER])) {
            throw new InvalidActionException('exceptions.game_profile_not_valid');
        }

        // check gamelancer is playing
        $schedule = array_get($params, 'schedule');
        $isPlaying = $this->hasPlayingSession($gameProfile->user_id);
        $notAvailableNow = $isPlaying && !$schedule;
        if ($notAvailableNow) {
            throw new InvalidSessionNowException('exceptions.invalid_session_now');
        }

        $params['gameProfile'] = $gameProfile;
        $this->checkGamelancerOffline($params);
    }

    public function checkGamelancerOffline($params)
    {
        $gameProfile = array_get($params, 'gameProfile');
        if (!$gameProfile) {
            $gameProfileId = array_get($params, 'game_profile_id');
            $gameProfile = GameProfile::where('id', $gameProfileId)
                ->where('is_active', Consts::TRUE)
                ->first();
        }
        $this->validateSession($gameProfile, 'exceptions.game_profile_not_existed');

        if ($this->isFreeGameProfile($gameProfile->id)) {
            return true;
        }

        $schedule = array_get($params, 'schedule');
        $isTimeAvailable = $this->checkBookTimeInAvailable($params, $gameProfile->user_id);
        $gamelancerOffline = empty($schedule) && !UserOnlineUtils::isUserOnline($gameProfile->user_id) && !$isTimeAvailable;
        if ($gamelancerOffline) {
            $this->createNotifyGamelancerOffline($gameProfile);
            throw new GamelancerOfflineException();
        }

        return true;
    }

    private function checkBookTimeInAvailable($params, $userId)
    {
        $quantity = array_get($params, 'quantity', 1);
        $quantityDuration = 1; // default 1 game = 1 hour until has game duration

        Carbon::setWeekStartsAt(Carbon::SUNDAY);
        $now = Carbon::now();
        $dayOfWeek = Carbon::now()->dayOfWeek;
        $startOfDay = Carbon::now()->startOfDay();
        $todayMinutes = $now->diffInMinutes($startOfDay);
        $playingMinutes = (float) $quantity * $quantityDuration;
        $minutesOfStart = $dayOfWeek * Consts::MINUTES_OF_DAY + $todayMinutes;
        $minutesOfEnd = $minutesOfStart + $playingMinutes;

        $data = [
            [
                'from' => $minutesOfStart,
                'to' => $minutesOfEnd
            ]
        ];

        if ($minutesOfEnd > Consts::MINUTES_OF_WEEK) {
            $data = [
                [
                    'from' => $minutesOfStart,
                    'to' => Consts::MINUTES_OF_WEEK
                ],
                [
                    'from' => 0,
                    'to' => $minutesOfEnd - Consts::MINUTES_OF_WEEK
                ]
            ];
        }

        $flag = true;
        foreach ($data as $value) {
            $flag = GamelancerAvailableTime::where('user_id', $userId)
                ->where('from', '<=', $value['from'])
                ->where('to', '>=', $value['to'])
                ->exists();

            if (!$flag) {
                break;
            }
        }

        return $flag;
    }

    private function validateSession($session, $key = 'exceptions.invalid_action')
    {
        if (!$session) {
            throw new InvalidActionException($key);
        }
    }

    private function hasPlayingSession($userId)
    {
        return Session::whereIn('status', [Consts::SESSION_STATUS_RUNNING, Consts::SESSION_STATUS_STARTING, Consts::SESSION_STATUS_MARK_COMPLETED])
            ->where(function ($query) use ($userId) {
                $query->where('gamelancer_id', $userId)
                    ->orWhere('claimer_id', $userId);
            })
            ->exists();
    }

    private function updateSessionTasks($session, $action)
    {
        // CheckSessionResponseInvitation -----------> auto reject when not response invite
        // CheckSessionScheduleExpiredTime ----------> auto reject when over schedule
        // ProcessSessionCheckReady -----------------> send email before starting + starting + absent user
        // ProcessSessionCompleted ------------------> complete session
        $scheduleSession = $session->type === Consts::SESSION_TYPE_SCHEDULE;
        switch ($action) {
            case Consts::SESSION_ACTION_BOOK_FREE:
                // CheckSessionResponseInvitation::addSession($session);
                break;

            case Consts::SESSION_ACTION_BOOK_PAID:
                CheckSessionResponseInvitation::addSession($session);
                if ($scheduleSession) {
                    CheckSessionScheduleExpiredTime::addSession($session);
                }
                break;

            case Consts::SESSION_ACTION_CANCEL_FREE:
                // CheckSessionResponseInvitation::removeSession($session);
                break;
            case Consts::SESSION_ACTION_CANCEL_PAID:
                CheckSessionResponseInvitation::removeSession($session);
                if ($scheduleSession) {
                    CheckSessionScheduleExpiredTime::removeSession($session);
                }
                break;

            case Consts::SESSION_ACTION_REJECT_FREE:
                // CheckSessionResponseInvitation::removeSession($session);
                break;

            case Consts::SESSION_ACTION_REJECT_PAID:
                CheckSessionResponseInvitation::removeSession($session);
                if ($scheduleSession) {
                    CheckSessionScheduleExpiredTime::removeSession($session);
                }
                break;

            case Consts::SESSION_ACTION_ACCEPT_FREE:
                // CheckSessionResponseInvitation::removeSession($session);
                ProcessSessionCheckReady::addSession($session);
                break;

            case Consts::SESSION_ACTION_ACCEPT_PAID:
                CheckSessionResponseInvitation::removeSession($session);
                ProcessSessionCheckReady::addSession($session);
                if ($scheduleSession) {
                    CheckSessionScheduleExpiredTime::removeSession($session);
                }
                break;

            case Consts::SESSION_ACTION_STARTING_SCHEDULE:
                ProcessSessionCheckReady::updateSession($session);
                break;

            case Consts::SESSION_ACTION_READY:
                if ($session->status === Consts::SESSION_STATUS_RUNNING) {
                    ProcessSessionCheckReady::removeSession($session);
                    ProcessSessionCompleted::addSession($session);
                } else {
                    ProcessSessionCheckReady::updateSession($session);
                }
                break;

            case Consts::SESSION_ACTION_ACCEPT_ADDTIME:
                ProcessSessionCompleted::updateSession($session);
                break;

            case Consts::SESSION_ACTION_STOP:
                ProcessSessionCompleted::removeSession($session);
                break;

            case Consts::SESSION_ACTION_COMPLETE_FREE:
            case Consts::SESSION_ACTION_COMPLETE_PAID:
                ProcessSessionCompleted::removeSession($session);
                break;

            default:
                break;
        }
    }

    private function fireSessionTabUpdated($session)
    {
        event(new SessionTabUpdated($session->gamelancer_id, $session->id));
        event(new SessionTabUpdated($session->claimer_id, $session->id));
    }

    private function createGamelancerNotification($session, $action, $params = [])
    {
        $type = Consts::NOTIFY_TYPE_SESSION;
        $message = null;
        $props = [];
        $data = [
            'user' => (object) ['id' => $session->claimer_id],
            'channel_id' => Channel::where('id', $session->channel_id)->value('mattermost_channel_id')
        ];

        switch ($action) {
            case Consts::SESSION_ACTION_BOOK_FREE:
                $message = Consts::NOTIFY_SESSION_BOOK_FREE;
                $props = ['game_id' => $session->gameProfile->game->id];
                $data = array_merge(
                    $data,
                    [
                        'mailable' => new SessionBookedMail($session),
                        'smsable' => new SendSmsNotificationJob($session, Consts::NOTIFY_SMS_SESSION_BOOKED)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_CANCEL_FREE:
                $message = Consts::NOTIFY_SESSION_CANCEL_FREE;
                $props = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_COMPLETE_FREE:
                $message = Consts::NOTIFY_SESSION_COMPLETE_FREE;
                $props = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_BOOK_PAID:
                $scheduleSession = $session->type === Consts::SESSION_TYPE_SCHEDULE;
                $message = $scheduleSession ? Consts::NOTIFY_SESSION_BOOK : Consts::NOTIFY_SESSION_BOOK_NOW;
                $props = [
                    'game_id'       => $session->gameProfile->game->id,
                    'quantity'      => Utils::formatPropsValue($session->quantity),
                    'quantity_type' => $session->gameOffer->type,
                    'price'         => Utils::formatPropsValue($session->escrow_balance),
                    'date'          => $session->schedule_at
                ];
                $data = array_merge(
                    $data,
                    [
                        'mailable' => new SessionBookedMail($session),
                        'smsable' => new SendSmsNotificationJob($session, Consts::NOTIFY_SMS_SESSION_BOOKED)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_CANCEL_PAID:
                $message = Consts::NOTIFY_SESSION_CANCEL_PAID;
                $props = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_STOP:
                $rewards = array_get($params, 'rewards');
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_REWARDS;
                $message = Consts::NOTIFY_SESSION_STOP_GAMELANCER;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'rewards' => Utils::formatPropsValue($rewards),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::barToUsd($rewards)),
                    'reason' => array_get($params, 'reason')
                ];
                break;

            case Consts::SESSION_ACTION_COMPLETE_PAID:
                $rewards = array_get($params, 'rewards');
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_REWARDS;
                $message = Consts::NOTIFY_SESSION_COMPLETE_GAMELANCER;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'rewards' => Utils::formatPropsValue($rewards),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::barToUsd($rewards))
                ];
                break;

            case Consts::SESSION_ACTION_REVIEW:
                $star = array_get($params, 'star');
                $review = array_get($params, 'review');
                $tip = array_get($params, 'tip');
                $type = Consts::NOTIFY_TYPE_SESSION_REVIEW;
                $message = $tip ? Consts::NOTIFY_SESSION_REVIEW_WITH_TIP : Consts::NOTIFY_SESSION_REVIEW;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'star' => $star,
                    'rewards' => Utils::formatPropsValue(CurrencyExchange::coinToBar($tip))
                ];
                $data = array_merge(
                    $data,
                    [
                        'mailable' => new SessionReviewMail($session->gamelancer_id, $session, $review)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_STARTING_SCHEDULE:
                $message = Consts::NOTIFY_SESSION_START;
                break;

            case Consts::SESSION_ACTION_OUTDATED:
                $rewards = array_get($params, 'rewards');
                $type = $session->gamelancer_ready ? Consts::NOTIFY_TYPE_SESSION_WALLET_REWARDS : Consts::NOTIFY_TYPE_SESSION;
                $message = $session->gamelancer_ready ? Consts::NOTIFY_SESSION_USER_OUTDATED_GAMELANCER : Consts::NOTIFY_SESSION_GAMELANCER_OUTDATED_GAMELANCER;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'rewards' => Utils::formatPropsValue($rewards),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::barToUsd($rewards))
                ];
                break;

            case Consts::SESSION_ACTION_NOTIFY_SCHEDULE:
                $message = Consts::NOTIFY_SESSION_STARTING;
                $data = array_merge(
                    $data,
                    [
                        'smsable' => new SendSmsNotificationJob($session, Consts::NOTIFY_SMS_SESSION_STARTING, [$session->gamelancer_id, $session->claimer_id]),
                        'mailable' => new SessionStartingMail($session->gamelancer_id, $session->claimer_id, $session)
                    ]
                );
                break;

            default:
                break;
        }

        $notificationParams = [
            'user_id' => $session->gamelancer_id,
            'type' => $type,
            'message' => $message,
            'props' => $props,
            'data' => $data
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_SESSION, $notificationParams);
    }

    private function createUserNotification($session, $action, $params = [])
    {
        $type = Consts::NOTIFY_TYPE_SESSION;
        $message = null;
        $props = [];
        $data = ['user' => (object) ['id' => $session->gamelancer_id]];

        switch ($action) {
            case Consts::SESSION_ACTION_REJECT_FREE:
                $autoAction = array_get($params, 'auto_action');
                $message = $autoAction ? Consts::NOTIFY_SESSION_REJECT_AUTO_FREE : Consts::NOTIFY_SESSION_REJECT_FREE;
                $data = array_merge(
                    $data,
                    [
                        'mailable' => new SessionRejectedMail($session)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_ACCEPT_FREE:
                $message = Consts::NOTIFY_SESSION_ACCEPT_FREE;
                $props = [
                    'game_id' => $session->gameProfile->game->id
                ];
                break;

            case Consts::SESSION_ACTION_COMPLETE_FREE:
                $message = Consts::NOTIFY_SESSION_COMPLETE_FREE;
                $props = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_REJECT_PAID:
                $autoAction = array_get($params, 'auto_action');
                $escrowBalances = array_get($params, 'escrow_balance');
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_COINS;
                $message = $autoAction ? Consts::NOTIFY_SESSION_SYSTEM_REJECT : Consts::NOTIFY_SESSION_REJECT;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'coins' => Utils::formatPropsValue($escrowBalances)
                ];
                $data = array_merge(
                    $data,
                    [
                        'mailable' => new SessionRejectedMail($session)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_ACCEPT_PAID:
                $message = Consts::NOTIFY_SESSION_ACCEPT;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'quantity' => Utils::formatPropsValue($session->quantity),
                    'price' => Utils::formatPropsValue($session->escrow_balance)
                ];
                $data = array_merge(
                    $data,
                    [
                        'smsable' => new SendSmsNotificationJob($session, Consts::NOTIFY_SMS_SESSION_ACCEPTED),
                        'mailable' => new SessionAcceptedMail($session)
                    ]
                );
                break;

            case Consts::SESSION_ACTION_STOP:
                $coins = array_get($params, 'coins');
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_COINS;
                $message = Consts::NOTIFY_SESSION_STOP;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'coins' => Utils::formatPropsValue($coins),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::coinToUsd($coins))
                ];
                break;

            case Consts::SESSION_ACTION_COMPLETE_PAID:
                $coins = array_get($params, 'coins');
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_COINS;
                $message = Consts::NOTIFY_SESSION_COMPLETE;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'coins' => Utils::formatPropsValue($coins),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::coinToUsd($coins))
                ];
                break;

            case Consts::SESSION_ACTION_REVIEW:
                break;

            case Consts::SESSION_ACTION_STARTING_SCHEDULE:
                $message = Consts::NOTIFY_SESSION_START;
                break;

            case Consts::SESSION_ACTION_OUTDATED:
                $type = Consts::NOTIFY_TYPE_SESSION_WALLET_COINS;
                $message = $session->gamelancer_ready ? Consts::NOTIFY_SESSION_USER_OUTDATED_USER : Consts::NOTIFY_SESSION_GAMELANCER_OUTDATED_USER;
                $props = [
                    'game_id' => $session->gameProfile->game->id,
                    'coins' => Utils::formatPropsValue($session->escrow_balance),
                    'usd' => Utils::formatPropsValue(CurrencyExchange::coinToUsd($session->escrow_balance))
                ];
                break;

            case Consts::SESSION_ACTION_NOTIFY_SCHEDULE:
                $message = Consts::NOTIFY_SESSION_STARTING;
                $data = array_merge(
                    $data,
                    [
                        'smsable' => new SendSmsNotificationJob($session, Consts::NOTIFY_SMS_SESSION_STARTING, [$session->claimer_id, $session->gamelancer_id]),
                        'mailable' => new SessionStartingMail($session->claimer_id, $session->gamelancer_id, $session)
                    ]
                );
                break;

            default:
                break;
        }

        $notificationParams = [
            'user_id' => $session->claimer_id,
            'type' => $type,
            'message' => $message,
            'props' => $props,
            'data' => $data
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_SESSION, $notificationParams);
    }

    private function createSessionMessage($session, $action, $params = [])
    {
        $scheduleSession = $session->type === Consts::SESSION_TYPE_SCHEDULE;
        $message = [
            'sender' => Auth::id(),
            'type' => Consts::MESSAGE_TYPE_TEXT_MESSAGE,
            'key' => null,
            'props' => []
        ];

        switch ($action) {
            case Consts::SESSION_ACTION_BOOK_FREE:
                $message['type'] = Consts::MESSAGE_TYPE_BOOK_SESSION;
                $message['key'] = Consts::MESSAGE_SESSION_BOOK_FREE;
                $message['props'] = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_CANCEL_FREE:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_CANCEL_BOOK;
                $message['key'] = Consts::MESSAGE_SESSION_CANCEL_FREE;
                $message['props'] = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_REJECT_FREE:
                $this->updateLastSessionMessage($session);
                $autoAction = array_get($params, 'auto_action');
                $message['type'] = Consts::MESSAGE_TYPE_REJECT_BOOK;
                $message['key'] = $autoAction ? Consts::MESSAGE_SESSION_REJECT_AUTO_FREE : Consts::MESSAGE_SESSION_REJECT_FREE;
                $message['props'] = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_ACCEPT_FREE:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_ACCEPT_BOOK_NOW;
                $message['key'] = Consts::MESSAGE_SESSION_ACCEPT_FREE;
                break;

            case Consts::SESSION_ACTION_COMPLETE_FREE:
                $message['type'] = Consts::MESSAGE_TYPE_COMPLETE_SESSION;
                $message['key'] = Consts::MESSAGE_SESSION_COMPLETE_FREE;
                $message['props'] = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_BOOK_PAID:
                $message['type'] = Consts::MESSAGE_TYPE_BOOK_SESSION;
                $message['key'] = Consts::MESSAGE_SESSION_BOOK_PAID;
                $message['props'] = [
                    'game_id'       => $session->gameProfile->game->id,
                    'quantity'      => Utils::formatPropsValue($session->quantity),
                    'quantity_type' => $session->gameOffer->type,
                    'price'         => Utils::formatPropsValue($session->escrow_balance),
                    'date'          => $session->schedule_at
                ];
                break;

            case Consts::SESSION_ACTION_CANCEL_PAID:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_CANCEL_BOOK;
                $message['key'] = Consts::MESSAGE_SESSION_CANCEL_PAID;
                $message['props'] = ['game_id' => $session->gameProfile->game->id];
                break;

            case Consts::SESSION_ACTION_REJECT_PAID:
                $this->updateLastSessionMessage($session);
                $autoAction = array_get($params, 'auto_action');
                $escrowBalances = array_get($params, 'escrow_balance');
                $message['type'] = Consts::MESSAGE_TYPE_REJECT_BOOK;
                $message['key'] = $autoAction ? Consts::MESSAGE_SESSION_REJECT_AUTO_PAID : Consts::MESSAGE_SESSION_REJECT_PAID;
                $message['props'] = [
                    'game_id'   => $session->gameProfile->game->id,
                    'price'     => Utils::formatPropsValue($escrowBalances),
                    'reason'    => array_get($params, 'reason')
                ];
                break;

            case Consts::SESSION_ACTION_ACCEPT_PAID:
                $this->updateLastSessionMessage($session);
                if ($scheduleSession) {
                    $message['type'] = Consts::MESSAGE_TYPE_ACCEPT_BOOK_SCHEDULE;
                    $message['key'] = Consts::MESSAGE_SESSION_ACCEPT_PAID_SCHEDULE;
                    break;
                }
                $message['type'] = Consts::MESSAGE_TYPE_ACCEPT_BOOK_NOW;
                $message['key'] = Consts::MESSAGE_SESSION_ACCEPT_PAID_NOW;
                break;

            case Consts::SESSION_ACTION_READY:
                $this->updateReadySessionMessage($session);
                if ($session->status === Consts::SESSION_STATUS_RUNNING) {
                    $message['sender'] = null;
                    $message['key'] = Consts::MESSAGE_SESSION_STARTED;
                    break;
                }
                $message['type'] = Consts::MESSAGE_TYPE_READY;
                $message['key'] = $session->gamelancer_ready ? Consts::MESSAGE_SESSION_GAMELANCER_READY : Consts::MESSAGE_SESSION_USER_READY;
                break;

            case Consts::SESSION_ACTION_MARK_COMPLETE:
                $message['type'] = Consts::MESSAGE_TYPE_MARK_COMPLETE;
                $message['key'] = Consts::MESSAGE_SESSION_MARK_COMPLETE;
                break;

            case Consts::SESSION_ACTION_CONTINUE:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_CONTINUE;
                $message['key'] = Consts::MESSAGE_SESSION_CONTINUE;
                break;

            case Consts::SESSION_ACTION_REJECT_COMPLETE:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_REJECT_COMPLETE;
                $message['key'] = Consts::MESSAGE_SESSION_REJECT_COMPLETE;
                $message['props'] = ['reason' => array_get($params, 'reason')];
                break;

            case Consts::SESSION_ACTION_ADDTIME:
                $quantity = array_get($params, 'quantity');
                $escrowBalances = array_get($params, 'escrow_balance');
                $message['type'] = Consts::MESSAGE_TYPE_ADD_TIME;
                $message['key'] = Consts::MESSAGE_SESSION_ADD_TIME;
                $message['props'] = [
                    'quantity'  => round($quantity),
                    'price' => Utils::formatPropsValue($escrowBalances)
                ];
                break;

            case Consts::SESSION_ACTION_REJECT_ADDTIME:
                $this->updateLastSessionMessage($session);
                $quantity = array_get($params, 'quantity');
                $escrowBalances = array_get($params, 'escrow_balance');
                $message['type'] = Consts::MESSAGE_TYPE_RESPONSE_ADD_TIME;
                $message['key'] = Consts::MESSAGE_SESSION_REJECT_ADD_TIME;
                $message['props'] = [
                    'quantity'  => round($quantity),
                    'price' => Utils::formatPropsValue($escrowBalances)
                ];
                break;

            case Consts::SESSION_ACTION_ACCEPT_ADDTIME:
                $this->updateLastSessionMessage($session);
                $quantity = array_get($params, 'quantity');
                $escrowBalances = array_get($params, 'escrow_balance');
                $message['type'] = Consts::MESSAGE_TYPE_RESPONSE_ADD_TIME;
                $message['key'] = Consts::MESSAGE_SESSION_ACCEPT_ADD_TIME;
                $message['props'] = [
                    'quantity'  => round($quantity),
                    'price' => Utils::formatPropsValue($escrowBalances)
                ];
                break;

            case Consts::SESSION_ACTION_STOP:
                $this->updateCompleteSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_STOP_SESSION;
                $message['key'] = $session->gamelancer_stop ? Consts::MESSAGE_SESSION_GAMELANCER_STOP : Consts::MESSAGE_SESSION_USER_STOP;
                $message['props'] = ['reason' => array_get($params, 'reason')];
                break;

            case Consts::SESSION_ACTION_COMPLETE_PAID:
                $this->updateCompleteSessionMessage($session);
                $tip = array_get($params, 'tip');
                $message['type'] = Consts::MESSAGE_TYPE_COMPLETE_SESSION;
                $message['key'] = $tip ? Consts::MESSAGE_SESSION_COMPLETE_PAID_TIP : Consts::MESSAGE_SESSION_COMPLETE_PAID;
                $message['props'] = [
                    'game_id' => $session->gameProfile->game->id,
                    'rewards' => Utils::formatPropsValue(array_get($params, 'rewards')),
                    'quantity' => Utils::formatPropsValue($session->quantity_played),
                    'tip' => Utils::formatPropsValue($tip)
                ];
                break;

            case Consts::SESSION_ACTION_REVIEW:
                $this->updateLastSessionMessage($session);
                $star = array_get($params, 'star');
                $tip = array_get($params, 'tip');
                $message['type'] = Consts::MESSAGE_TYPE_REVIEW_SESSION;
                $message['key'] = $tip ? Consts::MESSAGE_SESSION_USER_REVIEW_WITH_TIP : Consts::MESSAGE_SESSION_USER_REVIEW;
                $message['props'] = [
                    'game_id' => $session->gameProfile->game->id,
                    'star' => Utils::formatPropsValue($star),
                    'coins' => Utils::formatPropsValue($tip),
                    'rewards' => Utils::formatPropsValue(CurrencyExchange::coinToBar($tip))
                ];
                break;

            case Consts::SESSION_ACTION_STARTING_SCHEDULE:
                $this->updateLastSessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_START_SESSION;
                $message['key'] = Consts::MESSAGE_SESSION_STARTING;
                $message['props'] = [
                    'game_id'       => $session->gameProfile->game->id,
                    'quantity'      => Utils::formatPropsValue($session->quantity),
                    'quantity_type' => $session->gameOffer->type,
                    'date'          => $session->schedule_at
                ];
                break;

            case Consts::SESSION_ACTION_OUTDATED:
                $this->updateReadySessionMessage($session);
                $message['type'] = Consts::MESSAGE_TYPE_OUTDATED_SESSION;
                if (!$session->claimer_ready && !$session->gamelancer_ready) {
                    $message['key'] = Consts::MESSAGE_SESSION_OUTDATED;
                    break;
                }
                if (!$session->claimer_ready) {
                    $message['key'] = Consts::MESSAGE_SESSION_USER_OUTDATED;
                    break;
                }
                $message['key'] = Consts::MESSAGE_SESSION_GAMELANCER_OUTDATED;
                break;

            default:
                break;
        }

        $systemMessage = $this->createChatSystemMessage(
            $session,
            $message,
            Consts::FALSE
        );

        $this->createPostMessage($session->channel->mattermost_channel_id, $message, $systemMessage);
    }

    private function fireBookSessionSuccess($session)
    {
        event(new SessionBooked($session->gamelancer_id, $session));
        event(new SessionBooked($session->claimer_id, $session));
    }

    private function calculateStatistic($session, $action)
    {
        switch ($action) {
            case Consts::SESSION_ACTION_STOP:
                CalculateGameProfileStatistic::dispatch($session->game_profile_id);
                CalculateGameStatistic::dispatch($session->gameProfile->game_id, $session, Consts::GAME_STATISTIC_SESSION_STOPPED)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);
                break;

            case Consts::SESSION_ACTION_COMPLETE_FREE:
            case Consts::SESSION_ACTION_COMPLETE_PAID:
                CalculateGameProfileStatistic::dispatch($session->game_profile_id);
                CalculateGameStatistic::dispatch($session->gameProfile->game_id, $session, Consts::GAME_STATISTIC_SESSION_COMPLETED)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);
                break;

            case Consts::SESSION_ACTION_REVIEW:
                CalculateGameProfileStatistic::dispatch($session->game_profile_id);
                CalculateUserRating::dispatch($session->gamelancer_id)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);
                break;

            default:
                break;
        }
    }

    private function isFreeGameProfile($gameProfileId)
    {
        $price = GameProfileOffer::where('game_profile_id', $gameProfileId)->value('price');
        return $this->checkEmptyPrice($price);
    }

    public function isFreeSession($session)
    {
        return $session->type === Consts::SESSION_TYPE_FREE;
    }

    private function checkEmptyPrice($price)
    {
        return !BigNumber::new($price)->comp(0);
    }

    private function allowRefundCancel($session)
    {
        $now = Carbon::now()->timestamp;
        $refundTime = Utils::millisecondsToCarbon($session->schedule_at)
            ->subSeconds(Consts::GAMEPROFILE_BOOK_ACCEPT_USER_CANCEL)
            ->timestamp;

        return $session->status === Consts::SESSION_STATUS_BOOKED
            || ($session->status === Consts::SESSION_STATUS_ACCEPTED && $now <= $refundTime);
    }

    private function getSessionReason($reasonId, $reasonContent, $reasonType)
    {
        $reason = Reason::where('id', $reasonId)->first();
        if (!$reason) {
            $reason = Reason::create([
                'object_type' => Consts::OBJECT_TYPE_SESSION,
                'reason_type' => $reasonType,
                'content'     => $reasonContent
            ]);
        }

        return $reason;
    }

    private function updateReadySessionMessage($session)
    {
        $systemMessage = SessionSystemMessage::where('object_id', $session->id)
            ->where('object_type', Consts::OBJECT_TYPE_SESSION)
            ->whereIn('message_type', [Consts::MESSAGE_TYPE_START_SESSION, Consts::MESSAGE_TYPE_ACCEPT_BOOK_NOW, Consts::MESSAGE_TYPE_READY])
            ->get();

        $data = $this->getSessionDetail($session->id);
        $userIds = [$session->gamelancer_id, $session->claimer_id];
        foreach ($systemMessage as $message) {
            $message->data = $data;
            $message->is_processed = $data->status !== Consts::SESSION_STATUS_STARTING ? Consts::TRUE : Consts::FALSE;
            $message->save();
            $this->eventSessionMessageUpdated($message->id, $userIds);
        }
    }

    private function updateCompleteSessionMessage($session)
    {
        $systemMessage = SessionSystemMessage::where('object_id', $session->id)
            ->where('object_type', Consts::OBJECT_TYPE_SESSION)
            ->whereIn('message_type', [Consts::MESSAGE_TYPE_MARK_COMPLETE, Consts::MESSAGE_TYPE_ADD_TIME, Consts::MESSAGE_TYPE_REJECT_COMPLETE])
            ->get();

        $userIds = [$session->gamelancer_id, $session->claimer_id];
        foreach ($systemMessage as $message) {
            $message->is_processed = Consts::TRUE;
            $message->save();
            $this->eventSessionMessageUpdated($message->id, $userIds);
        }


    }

    public function updateLastSessionMessage($session)
    {
        $latestSysMsg = SessionSystemMessage::getLatestSystemMessage($session->id, Consts::OBJECT_TYPE_SESSION);

        if (!$latestSysMsg) {
            return true;
        }

        $latestSysMsg->is_processed = Consts::TRUE;
        $latestSysMsg->save();

        $userIds = [$session->gamelancer_id, $session->claimer_id];
        $this->eventSessionMessageUpdated($latestSysMsg->id, $userIds);
    }

    private function createSessionTransaction($session, $amountReceive, $amountPaid)
    {
        $this->createSessionWithdrawTransaction($session, $amountPaid);
        $this->createSessionDepositTransaction($session, $amountReceive);
    }

    private function createSessionWithdrawTransaction($session, $amountPaid)
    {
        $props = [
            'amount' => Utils::trimFloatNumber($amountPaid),
            'game_id' => $session->gameProfile->game->id,
            'quantity' => Utils::trimFloatNumber($session->quantity),
            'quantityCurrency' => $session->gameOffer->type === Consts::GAME_TYPE_HOUR ? 'h' : 'g',
            'user_id' => $session->gamelancer_id
        ];
        $data = [
            'currency'          => Consts::CURRENCY_COIN,
            'amount'            => $amountPaid,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_WITHDRAW,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => Consts::MESSAGE_TRANSACTION_SESSION_WITHDRAW,
            'message_props'     => $props,
            'internal_type'     => Consts::OBJECT_TYPE_SESSION,
            'internal_type_id'  => $session->id
        ];
        $this->transactionService->createTransaction($session->claimer_id, $data);
    }

    private function createSessionDepositTransaction($session, $amountReceive)
    {
        $props = [
            'amount' => Utils::trimFloatNumber($amountReceive),
            'game_id' => $session->gameProfile->game->id,
            'quantity' => Utils::trimFloatNumber($session->quantity),
            'quantityCurrency' => $session->gameOffer->type === Consts::GAME_TYPE_HOUR ? 'h' : 'g',
            'user_id' => $session->claimer_id
        ];
        $data = [
            'currency'          => Consts::CURRENCY_BAR,
            'amount'            => $amountReceive,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_DEPOSIT,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => Consts::MESSAGE_TRANSACTION_SESSION_DEPOSIT,
            'message_props'     => $props,
            'internal_type'     => Consts::OBJECT_TYPE_SESSION,
            'internal_type_id'  => $session->id
        ];
        $this->transactionService->createTransaction($session->gamelancer_id, $data);
    }

    private function payWage($userId, $balance, $fee)
    {
        $barReceived = CurrencyExchange::coinToBar($balance);
        $amountFee = BigNumber::new($barReceived)->mul($fee)->toString();
        $actuallyReceived = BigNumber::new($barReceived)->sub($amountFee)->toString();

        $this->userService->addMoreBalance($userId, $actuallyReceived, Consts::CURRENCY_BAR);
        return $actuallyReceived;
    }

    private function escrowSession($userId, $escrowBalances)
    {
        $userBalances = $this->userService->getUserBalances($userId);
        if ($userBalances->coin < $escrowBalances) {
            throw new NotEnoughBalancesException();
        }

        $this->userService->subtractBalance($userId, $escrowBalances);
    }

    public function checkBookingAnotherGamelancer($gameProfileId)
    {
        $gameProfile = GameProfile::where('id', $gameProfileId)
            ->where('is_active', Consts::TRUE)
            ->first();
        $this->validateSession($gameProfile, 'exceptions.game_profile_not_existed');

        return $anotherBooking = Session::where('claimer_id', Auth::id())
            ->where('status', Consts::SESSION_STATUS_BOOKED)
            ->where('gamelancer_id', '!=', $gameProfile->user_id)
            ->exists();
    }

    private function createNotifyGamelancerOffline($gameProfile)
    {
        $notificationParams = [
            'user_id' => $gameProfile->user_id,
            'type' => Consts::NOTIFY_TYPE_SESSION,
            'message' => Consts::NOTIFY_SESSION_BOOK_NOW_GAMELANCER_OFFLINE,
            'props' => ['game_id' => $gameProfile->game->id],
            'data' => [
                'user' => (object) ['id' => Auth::id()],
                'mailable' => new BookingSessionWhenGamelancerOfflineMail(Auth::id(), $gameProfile)
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_SESSION, $notificationParams);
    }

    private function validateOverlapSessionSchedule($userId, $startAt, $quantity, $data = [])
    {
        $quantityMinutes = BigNumber::new($quantity)->mul(60)->toString(); // minutes
        $userScheduleStartAt = Utils::millisecondsToCarbon($startAt);
        $userScheduleEndAt = $userScheduleStartAt->copy()->addMinutes($quantityMinutes);

        $statusList = [
            Consts::SESSION_STATUS_ACCEPTED,
            Consts::SESSION_STATUS_RUNNING,
            Consts::SESSION_STATUS_STARTING,
            Consts::SESSION_STATUS_MARK_COMPLETED
        ];

        $type = array_get($data, 'type', null);
        switch ($type) {
            case Consts::SESSION_ACTION_ADDTIME:
                $statusList =  [
                    Consts::SESSION_STATUS_ACCEPTED,
                    Consts::SESSION_STATUS_STARTING,
                    Consts::SESSION_STATUS_MARK_COMPLETED
                ];
                break;
        }

        $isOverlapSchedule = $this->userService->getSessionBookedSlots($userId, false)
            ->filter(function ($item) use ($statusList) {
                return in_array($item->status, $statusList);
            })
            ->contains(function ($item) use ($userScheduleStartAt, $userScheduleEndAt) {
                $quantityHours = $item->quantity ?: 1; // free quantity is null
                $itemQuantityMinutes = BigNumber::new($quantityHours)->mul(60)->toString(); // minutes
                $itemStart = $item->schedule_at ?? $item->start_at;
                $from = Utils::millisecondsToCarbon($itemStart);
                $to = Utils::millisecondsToCarbon($itemStart)->addMinutes($itemQuantityMinutes);

                return ($userScheduleStartAt->gte($from) && $userScheduleStartAt->lt($to))
                    || ($userScheduleEndAt->gt($from) && $userScheduleEndAt->lte($to))
                    || ($from->gte($userScheduleStartAt) && $from->lt($userScheduleEndAt))
                    || ($to->gt($userScheduleStartAt) && $to->lte($userScheduleEndAt));
            });

        if (!$isOverlapSchedule) {
            return;
        }

        throw new InvalidSessionScheduleException($data['exceptions_key']);
    }

    private function getOfferByQuantity($gameProfileId, $params)
    {
        $quantity = array_get($params, 'quantity');
        // not happend, quantity min = 1
        $quantity = $quantity < 1 ? 1 : $quantity;

        return GameProfileOffer::where('game_profile_id', $gameProfileId)
            ->where('type', array_get($params, 'type'))
            ->where('quantity', '<=', $quantity)
            ->orderBy('quantity', 'desc')
            ->first();
    }

    private function calculatePriceByOffer($offer, $quantity)
    {
        return BigNumber::new($offer->price)->div(BigNumber::new($offer->quantity))->mul(BigNumber::new($quantity))->toString();
    }

    private function calculateRealHoursPlayed($session)
    {
        $secondsPlayed = BigNumber::new($session->end_at)->sub(BigNumber::new($session->ready_at))->div(1000)->toString(); // seconds
        $hoursPlayed = BigNumber::new($secondsPlayed)
            ->div(60, BigNumber::ROUND_MODE_FLOOR)
            ->div(60, BigNumber::ROUND_MODE_FLOOR)
            ->toString(); // hours

        return BigNumber::round($hoursPlayed, BigNumber::ROUND_MODE_FLOOR, 2);
    }

    public function getSessionBookedSlots($userId, $includeBooked = true)
    {
        return $this->userService->getSessionBookedSlots($userId, $includeBooked)->map(function ($item) {
            return [
                'gamerInfo' => $item->claimerInfo,
                'title' => $item->gameProfile->title,
                'quantity' => $item->quantity,
                'schedule_at' => $item->schedule_at,
                'claimer_id' => $item->claimer_id
            ];
        });
    }

    public function getSessionBookedSlotsAsUser($userId)
    {
        return $this->userService->getSessionBookedSlotsAsUser($userId)->map(function ($item) {
            return [
                'gamerInfo' => $item->gamelancerInfo,
                'title' => $item->gameProfile->title,
                'quantity' => $item->quantity ?? 1, // 1 hour for free session
                'schedule_at' => $item->schedule_at,
                'gamelancer_id' => $item->gamelancer_id
            ];
        });
    }

    public function getPlayingSessionPairUser($partnerId)
    {
        $userId = Auth::id();
        $session = Session::with(['gameProfile', 'gameOffer', 'pendingRequests'])
            ->select('sessions.*', 'channels.mattermost_channel_id')
            ->join('channels', 'sessions.channel_id', 'channels.id')
            ->where(function ($query) use ($userId, $partnerId) {
                $query->where(function ($query2) use ($userId, $partnerId) {
                    $query2->where('sessions.gamelancer_id', $userId)
                        ->where('sessions.claimer_id', $partnerId);
                })
                ->orWhere(function ($query2) use ($userId, $partnerId) {
                    $query2->where('sessions.gamelancer_id', $partnerId)
                        ->where('sessions.claimer_id', $userId);
                });
            })
            ->whereIn('sessions.status', [Consts::SESSION_STATUS_STARTING, Consts::SESSION_STATUS_RUNNING, Consts::SESSION_STATUS_MARK_COMPLETED])
            ->orderBy('sessions.updated_at', 'desc')
            ->first();

        if ($session) {
            $userEarn = Tip::where('object_id', $session->id)
                ->where('receiver_id', $session->claimer_id)
                ->where('type', Consts::OBJECT_TYPE_SESSION)
                ->sum('tip');

            $gamelancerEarn = Tip::where('object_id', $session->id)
                ->where('receiver_id', $session->gamelancer_id)
                ->where('type', Consts::OBJECT_TYPE_SESSION)
                ->sum('tip');

            $session->user_tip_earn = $userEarn;
            $session->gamelancer_tip_earn = $gamelancerEarn;
        }

        return $session;
    }

    public function getTipOnUpdated($session) {
        $userEarn = Tip::where('object_id', $session->id)
            ->where('receiver_id', $session->claimer_id)
            ->where('type', Consts::OBJECT_TYPE_SESSION)
            ->sum('tip');

        $gamelancerEarn = Tip::where('object_id', $session->id)
            ->where('receiver_id', $session->gamelancer_id)
            ->where('type', Consts::OBJECT_TYPE_SESSION)
            ->sum('tip');
        $data = [
            'session_id' => $session->id,
            'user_tip_earn' => $userEarn,
            'gamelancer_tip_earn' => $gamelancerEarn
        ];
        return $data;
    }

    public function sendNotifyEmailStartingSession($session)
    {
        $session = Session::find($session->id);
        $this->validateSession($session);

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_NOTIFY_SCHEDULE);
        $this->createUserNotification($session, Consts::SESSION_ACTION_NOTIFY_SCHEDULE);
    }

    public function startingScheduleSession($sessionId)
    {
        $session = Session::find($sessionId);
        $this->validateSession($session);

        $session->status = Consts::SESSION_STATUS_STARTING;
        $session->start_at = $session->schedule_at;
        $session->save();

        $this->updateSessionTasks($session, Consts::SESSION_ACTION_STARTING_SCHEDULE);

        $this->fireSessionPlayingUpdated($session);
        $this->fireSessionTabUpdated($session);

        $this->performFirehosePut($session, 'Starting session');

        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_STARTING_SCHEDULE);
        $this->createUserNotification($session, Consts::SESSION_ACTION_STARTING_SCHEDULE);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_STARTING_SCHEDULE);

        return $session;
    }

    public function handleUserAbsent($sessionId)
    {
        $session = Session::find($sessionId);
        $this->validateSession($session);

        $actuallyReceived = 0;
        if (!$this->isFreeSession($session)) {
            if (!$session->gamelancer_ready) {
                $this->userService->addMoreBalance($session->claimer_id, $session->escrow_balance);
            }

            if ($session->gamelancer_ready && !$session->claimer_ready) {
                $actuallyReceived = $this->payWage($session->gamelancer_id, $session->escrow_balance, $session->fee);
                $this->createSessionTransaction($session, $actuallyReceived, $session->escrow_balance);
            }
        }

        $session->status = Consts::SESSION_STATUS_OUTDATED;
        $session->save();

        $this->fireSessionPlayingUpdated($session);

        $notifyParams = [
            'rewards' => $actuallyReceived,
        ];
        $this->createGamelancerNotification($session, Consts::SESSION_ACTION_OUTDATED, $notifyParams);
        $this->createUserNotification($session, Consts::SESSION_ACTION_OUTDATED, $notifyParams);

        $this->createSessionMessage($session, Consts::SESSION_ACTION_OUTDATED, $notifyParams);

        return $session;
    }

    private function performFirehosePut($session, $when)
    {
        $data = [
            'when' => $when,
            'data' => [
                'session_id' => $session->id,
                'gamelancer_id' => $session->gamelancer_id,
                'claimer_id' => $session->claimer_id,
                'game_id' => $session->gameProfile->game_id,
                'escrow_balance' => BigNumber::new($session->escrow_balance)->toString(),
                'session_status' => $session->status
            ]
        ];
        Aws::performFirehosePut($data);
    }

    private function collectUserTasking($session, $type)
    {
        CollectTaskingJob::dispatch($session->gamelancer_id, $type);
        CollectTaskingJob::dispatch($session->claimer_id, $type);
    }

    public function getDataBubbleChat($channelIds)
    {
        $userId = Auth::id();
        $sessions = Session::select('sessions.*', 'channels.mattermost_channel_id')
            ->join('channels', 'sessions.channel_id', 'channels.id')
            ->where(function ($query) use ($userId) {
                $query->where('sessions.gamelancer_id', $userId)
                    ->orWhere('sessions.claimer_id', $userId);
            })
            ->whereIn('sessions.status', [Consts::SESSION_STATUS_STARTING, Consts::SESSION_STATUS_RUNNING, Consts::SESSION_STATUS_MARK_COMPLETED])
            ->orderBy('sessions.updated_at', 'desc')
            ->get();

        $sessionChannelIds = $sessions->pluck('channel_id')->toArray();
        $mattermostChannelIds = Channel::whereIn('id', array_merge($channelIds, $sessionChannelIds))->pluck('mattermost_channel_id')->toArray();
        $channels = $this->chatService->getChannelsForUserByIds($mattermostChannelIds);

        return [
            'sessions' => $sessions,
            'channels' => $channels
        ];
    }
}
