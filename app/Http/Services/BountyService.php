<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\CurrencyExchange;
use App\Utils\UserOnlineUtils;
use App\Models\Bounty;
use App\Models\BountyClaimRequest;
use App\Models\BountyServer;
use App\Models\Setting;
use App\Models\SessionReview;
use App\Models\SessionSystemMessage;
use App\Models\UserFollowing;
use App\Models\Reason;
use App\Models\User;
use App\Http\Services\UserService;
use App\Http\Services\ChatService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\Reports\ClaimBountyException;
use App\Exceptions\Reports\ClaimBountyExistedException;
use App\Exceptions\Reports\ReviewBountyException;
use App\Exceptions\Reports\BountyAlreadyReviewException;
use App\Jobs\CheckBountyExpiredTime;
use App\Jobs\CalculateUserRating;
use App\Jobs\RejectAllRequestsBountyPendingJob;
use App\Jobs\SendSmsNotificationJob;
use Carbon\Carbon;
use App\Events\SessionTabUpdated;
use App\Events\BountyUpdated;
use App\Events\BountyInfoUpdated;
use Auth;
use Exception;
use Mail;
use App\Mails\SendBountyClaimedMail;
use App\Mails\SendBountyApprovedMail;
use App\Mails\SendBountyRejectedMail;
use App\Mails\SendBountyCanceledMail;
use App\Mails\SendBountyDisputedMail;
use App\Mails\SendBountyStoppedMail;
use App\Mails\BountyOnlineMail;
use App\Mails\BountyReviewMail;
use App\Mails\NewBountyMail;
use App\Traits\SessionTrait;
use Validator;
use SystemNotification;
use Aws;

class BountyService extends BaseService {

    use SessionTrait;

    protected $userService;

    public function __construct()
    {
        $this->userService          = new UserService();
        $this->chatService          = new ChatService();
        $this->transactionService   = new TransactionService();
    }

    public function getAllBounties($params)
    {
        $searchKey = array_get($params, 'search_key');
        $sortBy = array_get($params, 'sortBy');

        return Bounty::join('users', 'bounties.user_id', 'users.id')
            ->join('user_statistics', 'bounties.user_id', 'user_statistics.user_id')
            ->select('bounties.*', 'user_statistics.total_followers', 'user_statistics.rating')
            ->whereNull('bounty_claim_request_id')
            // ->with(['requests', 'game', 'bountyPlatforms', 'bountyServers', 'user'])
            ->with(['requests', 'game', 'bountyPlatforms', 'user'])
            ->when(!empty(array_get($params, 'game_id')), function ($query) use ($params) {
                $query->where('game_id', array_get($params, 'game_id'));
            })
            ->when(!empty(array_get($params, 'user_id')), function ($query) use ($params) {
                $query->where('bounties.user_id', array_get($params, 'user_id'));
            })
            ->when(!empty(array_get($params, 'not_slug')), function ($query) use ($params) {
                $query->where('slug', '!=', array_get($params, 'not_slug'));
            })
            ->when(array_key_exists('gender', $params) && !is_null($params['gender']), function ($query) use ($params) {
                $query->whereHas('user', function ($query2) use ($params) {
                    $query2->where('sex', array_get($params, 'gender'));
                });
            })
            ->when(array_key_exists('language', $params) && !is_null($params['language']), function ($query) use ($params) {
                $searchKey = Utils::escapeLike(array_get($params, 'language'));
                $query->whereHas('user', function ($query2) use ($searchKey) {
                    $query2->where('languages', 'like', "%{$searchKey}%");
                });
            })
            ->when(array_key_exists('platform', $params) && !is_null($params['platform']), function ($query) use ($params) {
                $query->whereHas('bountyPlatforms', function ($query2) use ($params) {
                    $query2->where('platform_id', array_get($params, 'platform'));
                });
            })
            ->when(array_key_exists('price', $params) && !is_null($params['price']), function ($query) use ($params) {
                $searchKey = array_get($params, 'price');
                list($source, $target) = explode(Consts::CHAR_UNDERSCORE, $searchKey);
                $query->where('bounties.price', '>=', $source)
                    ->when(!is_null($target), function ($query2) use ($target) {
                        $query2->where('bounties.price', '<=', $target);
                    });
            })
            ->when(array_key_exists('online', $params) && !is_null($params['online']), function ($query) use ($params) {
                $userIdOnlines = UserOnlineUtils::getUserIdOnlines();

                if ($params['online']) {
                    return $query->whereIn('bounties.user_id', $userIdOnlines);
                }

                return $query->whereNotIn('bounties.user_id', $userIdOnlines);
            })
            // ->when(!empty(array_get($params, 'level')), function ($query) use ($params) {
            //     $query->whereHas('user', function ($query2) use ($params) {
            //         $query2->where('level', '>=', array_get($params, 'level'));
            //     });
            // })
            // ->when(!empty(array_get($params, 'region')), function ($query) use ($params) {
            //     $query->whereHas('bountyServers', function ($query2) use ($params) {
            //         $query2->where('game_server_id', array_get($params, 'region'));
            //     });
            // })
            // ->when(!empty(array_get($params, 'rank')), function ($query) use ($params) {
            //     $query->where('rank_id', array_get($params, 'rank'));
            // })
            ->when(!empty(array_get($params, 'slug')), function ($query) use ($params) {
                $query->whereHas('game', function ($query2) use ($params) {
                    $query2->where('slug', array_get($params, 'slug'));
                });
            })
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                $searchKey = Utils::escapeLike($searchKey);
                $query->where('title', 'LIKE', "%{$searchKey}%")
                    ->orWhere('description', 'LIKE', "%{$searchKey}%")
                    ->orWhereHas('game', function ($query2) use ($searchKey) {
                        $query2->where('title', 'LIKE', "%{$searchKey}%");
                    })
                    ->orWhereHas('user', function ($query2) use ($searchKey) {
                        $query2->where('username', 'LIKE', "%{$searchKey}%")
                            ->orWhere('full_name', 'LIKE', "%{$searchKey}%");
                    });
            })
            ->when(!empty($sortBy), function ($query) use ($sortBy) {
                switch ($sortBy) {
                    case Consts::SORT_BY_FOLLOWER:
                        $query->orderBy('total_followers', 'desc');
                        break;
                    case Consts::SORT_BY_REVIEW:
                        $query->orderBy('rating', 'desc');
                        break;
                    case Consts::SORT_BY_PRICE:
                        $query->orderBy('price', 'desc');
                        break;
                    case Consts::SORT_BY_NEWEST:
                        $query->orderBy('updated_at', 'desc');
                        break;
                    default:
                        break;
                }
            })
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getBountyDetail($slug)
    {
        return Bounty::where('slug', $slug)
            ->with(['requests', 'game', 'bountyPlatforms', 'user', 'claimBountyRequest', 'userLevelMeta'])
            // ->with(['requests', 'game', 'bountyPlatforms', 'bountyServers', 'user', 'claimBountyRequest', 'userLevelMeta'])
            ->first();
    }

    public function createBounty($params)
    {
        $price = array_get($params, 'price');
        $this->validatePrice($price);

        $slug = Str::slug(array_get($params, 'title'), '-');
        $slug = $this->generateBountySlugIfNeed($slug);

        $escrowBalance = BigNumber::new($price)->mul(Consts::CLAIM_BOUNTY_ESCROW_RATIO)->toString();
        $bounty = Bounty::create([
            'user_id'            => Auth::id(),
            'game_id'            => array_get($params, 'game_id'),
            'price'              => array_get($params, 'price'),
            'media'              => array_get($params, 'media', null),
            'escrow_balance'     => $escrowBalance,
            'title'              => array_get($params, 'title'),
            'description'        => array_get($params, 'description'),
            'gamelancer_type'    => array_get($params, 'gamelancer_type', Consts::BOUNTY_ALL_GAMELANCER),
            'slug'               => $slug,
            // 'rank_id'            => array_get($params, 'rank_id'),
            // 'user_level_meta_id' => array_get($params, 'user_level_meta_id'),
            'status'             => Consts::BOUNTY_STATUS_CREATED
        ]);

        $bounty->createOrUpdateBountyPlatfroms(array_get($params, 'platform_ids'));
        // $bounty->createOrUpdateBountyServers(array_get($params, 'server_ids'));

        $this->userService->subtractBalance($bounty->user_id, $bounty->escrow_balance);

        $this->sendEmailAndNotify(Auth::id(), $bounty);

        $data = [
            'when' => 'Create bounty',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        return $bounty;
    }

    private function generateBountySlugIfNeed ($slug)
    {
        $validator = Validator::make(['slug' => $slug], ['slug' => 'unique:bounties']);

        $timestamp = now()->timestamp;
        if ($validator->fails()) {
            return "$slug-{$timestamp}";
        }

        return $slug;
    }

    public function updateBounty($bountyId, $params)
    {
        $bounty = Bounty::findOrFail($bountyId);
        if (!$bounty->activeRequests->isEmpty()) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_request_exists');
        }

        $price = array_get($params, 'price');
        $isPriceChanged = !empty($price) && BigNumber::new($price)->comp($bounty->price) !== 0;

        if ($isPriceChanged) {
            $oldEscrowBalance = $bounty->escrow_balance;
            $this->validatePrice($price, $oldEscrowBalance);

            $params['escrow_balance'] = BigNumber::new($price)->mul(Consts::CLAIM_BOUNTY_ESCROW_RATIO)->toString();
        }

        $bounty = $this->saveData($bounty, $params);

        $bounty->createOrUpdateBountyPlatfroms(array_get($params, 'platform_ids'));
        // $bounty->createOrUpdateBountyServers(array_get($params, 'server_ids'));

        if ($isPriceChanged) {
            $moreEscrowBalance = BigNumber::new($bounty->escrow_balance)->sub($oldEscrowBalance)->toString();
            $this->userService->subtractBalance($bounty->user_id, $moreEscrowBalance);
        }

        event(new BountyInfoUpdated($bounty->id));

        $data = [
            'when' => 'Update bounty',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        return $bounty;
    }

    private function validatePrice($price, $escrowBalance = 0)
    {
        $userBalance = $this->userService->getUserBalances(Auth::id());
        if (BigNumber::new($userBalance->coin)->add($escrowBalance)->sub($price)->isNegative()) {
            throw new ClaimBountyException('exceptions.claim_bounty.not_enough_balance');
        }
    }

    public function deleteBounty($bountyId)
    {
        $bounty = Bounty::findOrFail($bountyId);
        if (!$bounty->activeRequests->isEmpty()) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_request_exists');
        }

        $this->userService->addMoreBalance($bounty->user_id, $bounty->escrow_balance);

        $data = [
            'when' => 'Delete bounty',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        return $bounty->delete();
    }

    public function completeBounty($bountyId)
    {
        $bounty = Bounty::findOrFail($bountyId);
        $this->validateBountyAfterClaim($bounty);
        $bounty->status = Consts::BOUNTY_STATUS_COMPLETED;
        $bounty->save();

        CheckBountyExpiredTime::removeBounty($bounty);
        RejectAllRequestsBountyPendingJob::dispatch($bounty);

        $amountBar = CurrencyExchange::coinToBar($bounty->price);
        $amountFee = BigNumber::new($amountBar)->mul($bounty->fee)->toString();
        $amountReceive = BigNumber::new($amountBar)->sub($amountFee)->toString();

        event(new BountyUpdated(Auth::id(), $bounty));
        event(new BountyUpdated($bounty->claimBountyRequest->gamelancer_id, $bounty));

        $this->userService->addMoreBalance($bounty->claimBountyRequest->gamelancer_id, $amountReceive, Consts::CURRENCY_BAR);

        $gamelancer = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bounty->claimBountyRequest->gamelancer_id)
            ->first();

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bounty->user_id)
            ->first();

        $this->createBountyTransaction($bounty, $amountReceive, $bounty->escrow_balance, $user, $gamelancer);

        $data = [
            'when' => 'Bounty completed',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [$bounty->user_id, $bounty->claimBountyRequest->gamelancer_id];

        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bounty->bounty_claim_request_id, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bounty->claimBountyRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_COMPLETE;

        SystemNotification::notifyBountyActivity(
            $gamelancer->id,
            Consts::NOTIFY_TYPE_BOUNTY_WALLET_REWARDS,
            Consts::NOTIFY_BOUNTY_COMPLETE_GAMELANCER,
            [
                'bounty' => $bounty->title,
                'username' => $user->username,
                'rewards' => Utils::trimFloatNumber(BigNumber::round($amountReceive, BigNumber::ROUND_MODE_FLOOR, 2)),
                'usd' => Utils::trimFloatNumber(BigNumber::round(CurrencyExchange::barToUsd($amountReceive), BigNumber::ROUND_MODE_FLOOR, 2))
            ],
            ['user' => $user]
        );

        SystemNotification::notifyBountyActivity(
            $user->id,
            Consts::NOTIFY_TYPE_BOUNTY_WALLET_COINS,
            Consts::NOTIFY_BOUNTY_COMPLETE,
            [
                'bounty' => $bounty->title,
                'username' => $gamelancer->username,
                'coins' => Utils::trimFloatNumber(BigNumber::round($bounty->price, BigNumber::ROUND_MODE_FLOOR, 2)),
                'usd' => Utils::trimFloatNumber(BigNumber::round(CurrencyExchange::coinToUsd($bounty->price), BigNumber::ROUND_MODE_FLOOR, 2))
            ],
            ['user' => $gamelancer]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bounty->bounty_claim_request_id
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bounty;
    }

    private function createBountyTransaction($bounty, $amountReceive, $amountPaid, $user, $gamelancer)
    {
        $propsWithdraw = [
            'amount' => Utils::trimFloatNumber(BigNumber::round($amountPaid, BigNumber::ROUND_MODE_FLOOR, 2)),
            'bounty' => $bounty->title,
            'username' => $gamelancer->username
        ];
        $dataWithdraw = [
            'currency'          => Consts::CURRENCY_COIN,
            'amount'            => $amountPaid,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_WITHDRAW,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => Consts::MESSAGE_TRANSACTION_BOUNTY_WITHDRAW,
            'message_props'     => $propsWithdraw,
            'internal_type'     => Consts::OBJECT_TYPE_BOUNTY,
            'internal_type_id'  => $bounty->id
        ];
        $this->transactionService->createTransaction($user->id, $dataWithdraw);

        $propsDeposit = [
            'amount' => Utils::trimFloatNumber(BigNumber::round($amountReceive, BigNumber::ROUND_MODE_FLOOR, 2)),
            'bounty' => $bounty->title,
            'username' => $user->username
        ];
        $dataDeposit = [
            'currency'          => Consts::CURRENCY_BAR,
            'amount'            => $amountReceive,
            'payment_type'      => Consts::PAYMENT_SERVICE_TYPE_INTERNAL,
            'type'              => Consts::TRANSACTION_TYPE_DEPOSIT,
            'status'            => Consts::TRANSACTION_STATUS_SUCCESS,
            'message_key'       => Consts::MESSAGE_TRANSACTION_BOUNTY_DEPOSIT,
            'message_props'     => $propsDeposit,
            'internal_type'     => Consts::OBJECT_TYPE_BOUNTY,
            'internal_type_id'  => $bounty->id
        ];
        $this->transactionService->createTransaction($gamelancer->id, $dataDeposit);
    }

    public function markCompleteBounty($bountyId)
    {
        $bounty = Bounty::findOrFail($bountyId);
        $this->validateBountyAfterClaim($bounty);
        $bounty->status = Consts::BOUNTY_STATUS_STOPPED;
        $bounty->stopped_at = Utils::currentMilliseconds();
        $bounty->save();

        CheckBountyExpiredTime::addBounty($bounty);

        event(new BountyUpdated(Auth::id(), $bounty));
        event(new BountyUpdated($bounty->user_id, $bounty));

        $data = [
            'when' => 'Bounty stopped',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bounty->user_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bounty->bounty_claim_request_id, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bounty->claimBountyRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_MARK_COMPLETE;

        $gamelancer = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bounty->claimBountyRequest->claimerInfo->id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bounty->user_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_MARK_COMPLETE,
            [
                'bounty' => $bounty->title,
                'username' => $gamelancer->username
            ],
            [
                'user' => $gamelancer,
                'mailable' => new SendBountyStoppedMail($bounty)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bounty->bounty_claim_request_id
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bounty;
    }

    public function disputeBounty($bountyId)
    {
        $bounty = Bounty::findOrFail($bountyId);
        $this->validateBountyAfterClaim($bounty);
        $bounty->status = Consts::BOUNTY_STATUS_DISPUTED;
        $bounty->save();

        CheckBountyExpiredTime::removeBounty($bounty);
        RejectAllRequestsBountyPendingJob::dispatch($bounty);

        event(new BountyUpdated($bounty->user_id, $bounty));
        event(new BountyUpdated($bounty->claimBountyRequest->gamelancer_id, $bounty));

        $data = [
            'when' => 'Bounty disputed',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bounty->claimBountyRequest->gamelancer_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bounty->bounty_claim_request_id, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bounty->claimBountyRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_DISPUTED;

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bounty->user_id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bounty->claimBountyRequest->gamelancer_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_DISPUTED,
            [
                'bounty' => $bounty->title,
                'username' => $user->username
            ],
            [
                'user' => $user,
                'mailable' => new SendBountyDisputedMail($bounty)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bounty->bounty_claim_request_id
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bounty;
    }

    private function validateBountyAfterClaim($bounty)
    {
        if (!$bounty->bounty_claim_request_id) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_not_exists');
        }

        if ($bounty->claimBountyRequest->status === Consts::BOUNTY_STATUS_COMPLETED) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_completed');
        }
    }

    public function cancelBountyFromGamelancer($bountyId, $reasonId, $reasonContent)
    {
        $bounty = Bounty::findOrFail($bountyId);

        if (!$bounty->bounty_claim_request_id) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_claim_request_not_exists');
        }

        if ($bounty->claimBountyRequest->gamelancer_id !== Auth::id()) {
            throw new ClaimBountyException('exceptions.claim_bounty.bounty_claim_request_invalid');
        }

        if (!$reasonId) {
            $reason = Reason::create([
                'object_type' => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type' => Consts::REASON_TYPE_CANCEL,
                'content'     => $reasonContent
            ]);
            $reasonId = $reason->id;
        }

        $claimBountyRequest = $bounty->claimBountyRequest;
        $claimBountyRequest->status = Consts::CLAIM_BOUNTY_REQUEST_STATUS_CANCELED;
        $claimBountyRequest->reason_id = $reasonId;
        $claimBountyRequest->save();

        $bounty->bounty_claim_request_id = null;
        $bounty->status = Consts::BOUNTY_STATUS_CREATED;
        $bounty->save();

        event(new SessionTabUpdated(Auth::id(), $claimBountyRequest->id, Consts::OBJECT_TYPE_BOUNTY));
        event(new SessionTabUpdated($bounty->user_id, $claimBountyRequest->id, Consts::OBJECT_TYPE_BOUNTY));

        $data = [
            'when' => 'Gamelancer cancel bounty after approved by User',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title,
                'claim_bounty_request_id' => $claimBountyRequest->id
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bounty->user_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($claimBountyRequest->id, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $claimBountyRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_CANCEL_CLAIM;

        $gamelancer = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $claimBountyRequest->gamelancer_id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bounty->user_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_CANCEL_CLAIM,
            [
                'bounty' => $bounty->title,
                'username' => $gamelancer->username
            ],
            [
                'user' => $gamelancer,
                'mailable' => new SendBountyCanceledMail($bounty)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $claimBountyRequest->id,
            Consts::TRUE
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bounty;
    }

    public function claim($bountyId, $description)
    {
        $bounty = Bounty::findOrFail($bountyId);
        if ($bounty->gamelancer_type !== Consts::BOUNTY_ALL_GAMELANCER && Auth::user()->user_type !== Consts::USER_TYPE_PREMIUM_GAMELANCER) {
            throw new ClaimBountyException('exceptions.claim_bounty.only_premium');
        }

        if ($bounty->bounty_claim_request_id) {
            throw new ClaimBountyException('exceptions.claim_bounty.approved');
        }

        $alreadyClaim = BountyClaimRequest::where('bounty_id', $bountyId)
            ->where('gamelancer_id', Auth::id())
            ->where('status', Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING)
            ->exists();
        if ($alreadyClaim) {
            throw new ClaimBountyExistedException();
        }

        // To do validate level

        $channel = $this->chatService->createDirectMessageChannel($bounty->user_id);

        $bountyClaimRequest = BountyClaimRequest::create([
            'bounty_id'     => $bountyId,
            'gamelancer_id' => Auth::id(),
            'channel_id'    => $channel->id,
            'description'   => $description,
            'status'        => Consts::CLAIM_BOUNTY_REQUEST_STATUS_PENDING
        ]);

        event(new SessionTabUpdated(Auth::id(), $bountyClaimRequest->id, Consts::OBJECT_TYPE_BOUNTY));
        event(new SessionTabUpdated($bounty->user_id, $bountyClaimRequest->id, Consts::OBJECT_TYPE_BOUNTY));

        $data = [
            'when' => 'Gamelancer claim bounty',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title,
                'claim_bounty_request_id' => $bountyClaimRequest->id
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bounty->user_id];
        $channelId = $channel->channel_id; //mattermost_channel_id
        $message = Consts::MESSAGE_BOUNTY_CLAIM;

        $gamelancer = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bountyClaimRequest->gamelancer_id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bounty->user_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_CLAIM,
            [
                'bounty' => $bounty->title,
                'username' => $gamelancer->username
            ],
            [
                'user' => $gamelancer,
                'smsable' => new SendSmsNotificationJob($bounty, Consts::NOTIFY_SMS_BOUNTY_RECEIVED, [Auth::id()]),
                'mailable' => new SendBountyClaimedMail($bounty)
            ]
        );

        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bountyClaimRequest->id
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bountyClaimRequest;
    }

    public function cancelClaim($bountyClaimRequestId, $reasonId, $reasonContent)
    {
        $bountyClaimRequest = BountyClaimRequest::findOrFail($bountyClaimRequestId);

        if (!$reasonId) {
            $reason = Reason::create([
                'object_type' => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type' => Consts::REASON_TYPE_CANCEL,
                'content'     => $reasonContent
            ]);
            $reasonId = $reason->id;
        }

        $bountyClaimRequest->status = Consts::CLAIM_BOUNTY_REQUEST_STATUS_CANCELED;
        $bountyClaimRequest->reason_id = $reasonId;
        $bountyClaimRequest->save();

        event(new SessionTabUpdated(Auth::id(), $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));
        event(new SessionTabUpdated($bountyClaimRequest->bounty->user_id, $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));

        $data = [
            'when' => 'Gamelancer cancel request bounty',
            'data' => [
                'bounty_id' => $bountyClaimRequest->bounty_id,
                'claim_bounty_request_id' => $bountyClaimRequest->id
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bountyClaimRequest->bounty->user_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bountyClaimRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_CANCEL_CLAIM;

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bountyClaimRequestId,
            Consts::TRUE
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bountyClaimRequest;
    }

    public function approve($bountyClaimRequestId)
    {
        $bountyClaimRequest = BountyClaimRequest::findOrFail($bountyClaimRequestId);

        if ($bountyClaimRequest->status === Consts::CLAIM_BOUNTY_REQUEST_STATUS_REJECTED) {
            throw new ClaimBountyException('exceptions.claim_bounty.rejected');
        }

        if ($bountyClaimRequest->status === Consts::CLAIM_BOUNTY_REQUEST_STATUS_CANCELED) {
            throw new ClaimBountyException('exceptions.claim_bounty.canceled');
        }

        $bounty = Bounty::findOrFail($bountyClaimRequest->bounty_id);
        if ($bounty->bounty_claim_request_id) {
            throw new ClaimBountyException('exceptions.claim_bounty.already_accept_another');
        }

        $bountyClaimRequest->status = Consts::CLAIM_BOUNTY_REQUEST_STATUS_APPROVED;
        $bountyClaimRequest->save();

        $bounty->bounty_claim_request_id = $bountyClaimRequestId;
        $bounty->status = Consts::BOUNTY_STATUS_STARTED;
        $bounty->fee = Setting::getValue(Consts::BOUNTY_FEE_KEY);
        $bounty->save();

        event(new SessionTabUpdated(Auth::id(), $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));
        event(new SessionTabUpdated($bountyClaimRequest->gamelancer_id, $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));

        $data = [
            'when' => 'User approved request bounty',
            'data' => [
                'bounty_id' => $bounty->id,
                'title' => $bounty->title,
                'claim_bounty_request_id' => $bountyClaimRequest->id
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [Auth::id(), $bountyClaimRequest->gamelancer_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bountyClaimRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_ACCEPT;

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bounty->user_id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bountyClaimRequest->gamelancer_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_ACCEPT,
            [
                'bounty' => $bounty->title,
                'username' => $user->username
            ],
            [
                'user' => $user,
                'smsable' => new SendSmsNotificationJob($bounty, Consts::NOTIFY_SMS_BOUNTY_ACCEPTED),
                'mailable' => new SendBountyApprovedMail($bounty)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bountyClaimRequestId
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bounty;
    }

    public function reject($bountyClaimRequestId, $reasonId, $reasonContent)
    {
        $bountyClaimRequest = BountyClaimRequest::findOrFail($bountyClaimRequestId);

        if ($bountyClaimRequest->status === Consts::CLAIM_BOUNTY_REQUEST_STATUS_APPROVED) {
            throw new ClaimBountyException('exceptions.claim_bounty.approved');
        }

        if ($bountyClaimRequest->status === Consts::CLAIM_BOUNTY_REQUEST_STATUS_CANCELED) {
            throw new ClaimBountyException('exceptions.claim_bounty.canceled');
        }

        if (!$bountyClaimRequest->bounty) {
            throw new InvalidActionException('exceptions.bounty_not_existed');
        }

        if (!$reasonId) {
            $reason = Reason::create([
                'object_type' => Consts::OBJECT_TYPE_BOUNTY,
                'reason_type' => Consts::REASON_TYPE_CANCEL,
                'content'     => $reasonContent
            ]);
            $reasonId = $reason->id;
        }

        $bountyClaimRequest->status = Consts::CLAIM_BOUNTY_REQUEST_STATUS_REJECTED;
        $bountyClaimRequest->reason_id = $reasonId;
        $bountyClaimRequest->save();

        event(new SessionTabUpdated($bountyClaimRequest->bounty->user_id, $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));
        event(new SessionTabUpdated($bountyClaimRequest->gamelancer_id, $bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY));

        $data = [
            'when' => 'User rejected request bounty',
            'data' => [
                'bounty_id' => $bountyClaimRequest->bounty_id,
                'claim_bounty_request_id' => $bountyClaimRequest->id
            ]
        ];
        Aws::performFirehosePut($data);

        $userIds = [$bountyClaimRequest->bounty->user_id, $bountyClaimRequest->gamelancer_id];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bountyClaimRequestId, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bountyClaimRequest->channel->mattermost_channel_id;
        $message = Consts::MESSAGE_BOUNTY_REJECT;

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $bountyClaimRequest->bounty->user_id)
            ->first();

        SystemNotification::notifyBountyActivity(
            $bountyClaimRequest->gamelancer_id,
            Consts::NOTIFY_TYPE_BOUNTY,
            Consts::NOTIFY_BOUNTY_REJECT,
            [
                'bounty' => $bountyClaimRequest->bounty->title,
                'username' => $user->username
            ],
            [
                'user' => $user,
                'mailable' => new SendBountyRejectedMail($bountyClaimRequest)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);
        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bountyClaimRequestId,
            Consts::TRUE
        );
        $this->createPostMessage($channelId, $message, $systemMessage);

        return $bountyClaimRequest;
    }

    public function getBountyClaimForUser($input)
    {
        return Bounty::where('bounties.user_id', Auth::id())
            ->with(['requests'])
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getBountyClaimForGamelancer($input)
    {
        return BountyClaimRequest::where('gamelancer_id', Auth::id())
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function reviewBounty($bountyId, $params)
    {
        $bounty = Bounty::findOrFail($bountyId);
        if ($bounty->status !== Consts::BOUNTY_STATUS_COMPLETED) {
            throw new ReviewBountyException();
        }

        $bountyReviewExists = SessionReview::withTrashed()->where('object_id', $bountyId)
            ->where('object_type', Consts::OBJECT_TYPE_BOUNTY)
            ->where('reviewer_id', Auth::id())
            ->exists();

        if ($bountyReviewExists) {
            throw new BountyAlreadyReviewException();
        }

        $userId = $bounty->user_id === Auth::id() ? $bounty->claimBountyRequest->gamelancer_id : $bounty->user_id;
        $data = [
            'object_id' => $bountyId,
            'reviewer_id' => Auth::id(),
            'user_id' => $userId,
            'object_type' => Consts::OBJECT_TYPE_BOUNTY,
            'rate' => array_get($params, 'rate'),
            'description' => array_get($params, 'description'),
            'recommend' => array_get($params, 'recommend'),
            'submit_at' => Utils::currentMilliseconds()
        ];
        $review = SessionReview::create($data);

        $this->createSessionReviewTags($review, array_get($params, 'tags', []));

        CalculateUserRating::dispatch($userId)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);

        if ($bounty->user_id === Auth::id()) {
            $bounty->user_has_review = Consts::TRUE;
            $message = Consts::MESSAGE_BOUNTY_USER_REVIEW;
        } else {
            $bounty->claimer_has_review = Consts::TRUE;
            $message = Consts::MESSAGE_BOUNTY_GAMELANCER_REVIEW;
        }
        $bounty->save();

        $userIds = [Auth::id(), $userId];
        $latestSystemMessage = SessionSystemMessage::getLatestSystemMessage($bounty->bounty_claim_request_id, Consts::OBJECT_TYPE_BOUNTY);
        $channelId = $bounty->claimBountyRequest->channel->mattermost_channel_id;

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', Auth::id())
            ->first();

        SystemNotification::notifyBountyActivity(
            $userId,
            Consts::NOTIFY_TYPE_BOUNTY_REVIEW,
            Consts::NOTIFY_BOUNTY_REVIEW,
            [
                'bounty' => $bounty->title,
                'username' => $user->username,
                'star' => array_get($params, 'rate')
            ],
            [
                'user' => $user,
                'mailable' => new BountyReviewMail($userId, $bounty, $review)
            ]
        );

        $this->updateChatSystemMessage($userIds, $latestSystemMessage->id);

        $props = [
            'id' => $bounty->bounty_claim_request_id,
            'show_msg' => false
        ];
        if ($bounty->claimer_has_review && $bounty->user_has_review) {
            $props = [];
        }
        $messageProps = [
            'star' => array_get($params, 'rate')
        ];

        $systemMessage = $this->createChatSystemMessage(
            $userIds,
            $channelId,
            Consts::OBJECT_TYPE_BOUNTY,
            $message,
            $bounty->bounty_claim_request_id,
            $bounty->claimer_has_review && $bounty->user_has_review ? Consts::TRUE : Consts::FALSE,
            $messageProps
        );

        $this->createPostMessage($channelId, $message, $systemMessage);

        return $review;
    }

    private function sendEmailAndNotify($userId, $bounty)
    {
        $userIdList = UserFollowing::where('following_id', $userId)
            ->where('is_following', Consts::TRUE)
            ->pluck('user_id');

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $userId)
            ->first();

        // send to owner
        Mail::queue(new BountyOnlineMail($bounty));
        SystemNotification::notifyOther(
            $userId,
            Consts::NOTIFY_TYPE_SESSION_ONLINE,
            Consts::NOTIFY_YOUR_NEW_BOUNTY,
            [
                'bounty' => $bounty->title
            ],
            [
                'user' => $user,
                'type' => Consts::OBJECT_TYPE_BOUNTY,
                'bounty' => (object) ['id' => $bounty->id, 'slug' => $bounty->slug]
            ]
        );

        // send to followers
        $messageProps = [
            'bounty' => $bounty->title,
            'username' => $user->username
        ];

        foreach ($userIdList as $id) {
            $data = [
                'user' => $user,
                'type' => Consts::OBJECT_TYPE_BOUNTY,
                'bounty' => (object) ['id' => $bounty->id, 'slug' => $bounty->slug],
                'mailable' => new NewBountyMail($id, $bounty)
            ];
            SystemNotification::notifyFavoriteActivity($id, Consts::NOTIFY_TYPE_FAVORITE, Consts::NOTIFY_NEW_BOUNTY, $messageProps, $data);
        }
    }
}
