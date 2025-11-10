<?php

namespace App\Http\Services;

use App\Consts;
use App\Models\User;
use App\Models\SocicalNetwork;
use App\Models\Setting;
use App\Models\Admin;
use App\Models\Transaction;
use App\Models\UserBalance;
use App\Models\GamelancerInfo;
use App\Models\GameProfile;
use App\Models\InvitationCode;
use App\Utils;
use App\Utils\BigNumber;
use App\Models\Game;
use App\Models\Bounty;
use App\Models\BountyClaimRequest;
use App\Models\Session;
use App\Models\Offer;
use App\Models\GameType;
use App\Models\Banner;
use App\Models\Platform;
use App\Models\GameServer;
use App\Models\GameRank;
use App\Models\GamePlatform;
use App\Models\SessionReview;
use App\Models\GameProfileOffer;
use App\Models\UserSocialNetwork;
use App\Models\GamelancerAvailableTime;
use App\Models\UserRestrictPricing;
use App\Models\Ranking;
use App\Models\Tasking;
use App\Models\TaskingReward;
use App\Models\ExperiencePoint;
use Auth;
use Exception;
use Mail;
use App\Mails\ChangeUserStatusMailQueue;
use App\Mails\BecomeGamelancerApprovedMail;
use App\Mails\BecomeGamelancerApprovedFreeGamelancerMail;
use App\Mails\BecomeGamelancerRejectedMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Mails\MarketingEmailQueue;
use Carbon\Carbon;
use App\Events\UserSessionTerminated;
use App\Events\MasterdataUpdated;
use App\Events\UserUpdated;
use App\Events\UserProfileUpdated;
use App\Events\GameProfileUpdated;
use App\Traits\NotificationTrait;
use Illuminate\Support\Str;
use App\Jobs\AddKlaviyoMailList;
use App\Jobs\CalculateGameStatistic;
use App\Jobs\CalculateUserRatingWhenRemoveReview;
use Illuminate\Validation\ValidationException;

class AdminService {
    use NotificationTrait;

    public function getSocialNetworks()
    {
        return SocicalNetwork::all();
    }

    public function addSocialNetwork($input)
    {
        $socialNetwork = SocicalNetwork::updateOrCreate($input);
        MasterdataService::clearCacheOneTable('social_networks');
        return $socialNetwork;
    }

    public function updateSocialNetWork($socialNetworkId, $input)
    {
        $socialNetwork = SocicalNetwork::findOrFail($socialNetworkId);
        $socialNetwork->update($input);
        MasterdataService::clearCacheOneTable('social_networks');
        return $socialNetwork;
    }

    public function removeSocialNetwork($socialNetworkId)
    {
        $socialNetwork = SocicalNetwork::findOrFail($socialNetworkId);
        $socialNetwork->delete();
        MasterdataService::clearCacheOneTable('social_networks');
        return $socialNetwork;
    }

    public function getCurrentAdmin()
    {
        return Auth::guard('admin')->user();
    }

    public function getAdministrators($input)
    {
        return Admin::when(!empty($input['search_key']), function ($query) use ($input) {
                $searchKey = $input['search_key'];
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('email', 'like', '%' . $searchKey . '%')
                      ->orWhere('name', 'like', '%' . $searchKey . '%');
                });
            })
            ->when(
                !empty($input['sort']) && !empty($input['sort_type']),
                function ($query) use ($input) {
                    return $query->orderBy($input['sort'], $input['sort_type']);
                },
                function ($query) {
                    return $query->orderBy('created_at', 'asc');
                }
            )
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getGames($input)
    {
        return Game::when(
                        !empty($input['sort']) && !empty($input['sort_type']),
                        function ($query) use ($input) {
                            $query->orderBy($input['sort'], $input['sort_type']);
                        }, function ($query) {
                            $query->orderBy('order', 'asc');
                        }
                    )
                    ->when(isset($input['search_key']), function ($query) use ($input) {
                        $searchKey = $input['search_key'];
                        return $query->where(function ($q) use ($searchKey) {
                            $q->where('title', 'like', "%{$searchKey}%");
                        });
                    })
                    ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function editGame($id)
    {
        return Game::with(['platforms', 'servers', 'ranks'])
            ->select('id', 'title', 'slug', 'is_active', 'logo', 'portrait', 'thumbnail', 'thumbnail_hover', 'thumbnail_active', 'banner', 'cover', 'order', 'auto_order')
            ->where('id', $id)
            ->first();
    }

    private function key() {
        return 'Game:Code';
    }

    private function getCodeGames()
    {
        if (Cache::has($this->key())) {
            return Cache::get($this->key());
        }
        $value = Game::all()->pluck('code');
        Cache::put($this->key(), $value);
        return $value;
    }

    private function checkExistedCode($value) {
        $codes = $this->getCodeGames();
        foreach ($codes as $code) {
            if($code == $value) {
                return true;
            }
        }
        return false;
    }

    public function createGame($input)
    {
        $slug = Str::slug(
            array_get($input, 'slug', Str::slug($input['title'], Consts::CHAR_HYPHEN)),
            Consts::CHAR_HYPHEN
        );

        $logo = Utils::saveFileToStorage($input['logo'], 'data/g', "{$slug}_logo");
        $thumbnail = Utils::saveFileToStorage($input['thumbnail'], 'data/g', "{$slug}_thumbnail");
        $thumbnailHover = Utils::saveFileToStorage($input['thumbnail_hover'], 'data/g', "{$slug}_thumbnail_hover");
        $thumbnailActive = Utils::saveFileToStorage($input['thumbnail_active'], 'data/g', "{$slug}_thumbnail_active");
        $banner = Utils::saveFileToStorage($input['banner'], 'data/g', "{$slug}_banner");
        $portrait = Utils::saveFileToStorage($input['portrait'], 'data/g', "{$slug}_portrait");
        $cover = Utils::saveFileToStorage($input['cover'], 'data/g', "{$slug}_cover");
        $max_order = Game::orderBy('order', 'desc')->value('order');
        $game = Game::create([
            'title' => $input['title'],
            'slug' => $slug,
            'logo' => $logo,
            'thumbnail' => $thumbnail,
            'thumbnail_hover' => $thumbnailHover,
            'thumbnail_active' => $thumbnailActive,
            'banner' => $banner,
            'portrait' => $portrait,
            'cover' => $cover,
            'is_active' => $input['is_active'],
            'order' => $input['order'],
            'auto_order' => $input['auto_order']
        ]);
        $this->updateGameOrderOnCreate($game, $input['order'], $max_order);

        $platforms = json_decode($input['platforms'], true);
        if ($platforms) {
            $this->addGamePlatforms($game->id, $platforms);
        }

        $servers = json_decode($input['servers'], true);
        if (empty($servers)) {
            $servers = [Consts::DEFAULT_GAME_SERVER];
        }
        $this->addGameServers($game->id, $servers);

        $ranks = json_decode($input['ranks'], true);
        if (empty($ranks)) {
            $ranks = [Consts::DEFAULT_GAME_RANK];
        }
        $this->addGameRanks($game->id, $ranks);

        $this->addGameTypes($game->id);

        MasterdataService::clearCacheOneTable('games');

        return $game;
    }

    public function updateGameOrderOnCreate($gameCreated, $newOrd, $max_order)
    {
        if ($newOrd > $max_order) {
            $gameUpdated->order = $max_order + 1;
            $gameUpdated->save();
            return;
        }

        $gamesByOrder = Game::orderBy('order', 'asc')->orderBy('updated_at', 'desc')->get();
        $changeOrder = false;
        foreach ($gamesByOrder as $key => $game) {
            if ($game->id === $gameCreated->id) {
                $changeOrder = true;
                continue;
            }

            if ($changeOrder) {
                $game->order += 1;
                $game->save();
            }
        }
    }

    private function addGameTypes($gameId)
    {
        GameType::create([
            'game_id' => $gameId,
            'type' => Consts::GAME_TYPE_HOUR,
            'is_active' => Consts::TRUE
        ]);

        GameType::create([
            'game_id' => $gameId,
            'type' => Consts::GAME_TYPE_PER_GAME,
            'is_active' => Consts::TRUE
        ]);
    }

    private function addGamePlatforms($gameId, $platforms)
    {
        foreach ($platforms as $value) {
            $platformId = $value['id'];
            if (!$platformId) {
                $newPlatform = Platform::create([
                    'name' => $value['name']
                ]);
                $platformId = $newPlatform->id;
            }
            GamePlatform::create([
                'game_id' => $gameId,
                'platform_id' => $platformId
            ]);
        }
    }

    private function addGameServers($gameId, $servers)
    {
        foreach ($servers as $value) {
            GameServer::create([
                'game_id' => $gameId,
                'name' => $value
            ]);
        }
    }

    private function addGameRanks($gameId, $ranks)
    {
        foreach ($ranks as $value) {
            GameRank::create([
                'game_id' => $gameId,
                'name' => $value
            ]);
        }
    }

    public function updateGame($input)
    {
        $slug = Str::slug(
            array_get($input, 'slug', Str::slug($input['title'], Consts::CHAR_HYPHEN)),
            Consts::CHAR_HYPHEN
        );

        $logo = @file_exists($input['logo']) ? Utils::saveFileToStorage($input['logo'], 'data/g', "{$slug}_logo") : $input['logo'];
        $thumbnail = @file_exists($input['thumbnail']) ? Utils::saveFileToStorage($input['thumbnail'], 'data/g', "{$slug}_thumbnail") : $input['thumbnail'];
        $thumbnailHover = @file_exists($input['thumbnail_hover']) ? Utils::saveFileToStorage($input['thumbnail_hover'], 'data/g', "{$slug}_thumbnail_hover") : $input['thumbnail_hover'];
        $thumbnailActive = @file_exists($input['thumbnail_active']) ? Utils::saveFileToStorage($input['thumbnail_active'], 'data/g', "{$slug}_thumbnail_active") : $input['thumbnail_active'];
        $banner = @file_exists($input['banner']) ? Utils::saveFileToStorage($input['banner'], 'data/g', "{$slug}_banner") : $input['banner'];
        $portrait = @file_exists($input['portrait']) ? Utils::saveFileToStorage($input['portrait'], 'data/g', "{$slug}_portrait") : $input['portrait'];
        $cover = @file_exists($input['cover']) ? Utils::saveFileToStorage($input['cover'], 'data/g', "{$slug}_cover") : $input['cover'];

        $game = Game::findOrFail($input->id);
        $game->title = $input['title'];
        $game->slug = $slug;
        $game->is_active = $input['is_active'];
        $game->auto_order = $input['auto_order'];
        $game->logo = $logo;
        $game->thumbnail = $thumbnail;
        $game->thumbnail_hover = $thumbnailHover;
        $game->thumbnail_active = $thumbnailActive;
        $game->banner = $banner;
        $game->portrait = $portrait;
        $game->cover = $cover;
        $game->save();

        $max_order = Game::orderBy('order', 'desc')->value('order');
        if ($game->order != $input['order']) {
            $this->updateGameOrder($game, $input['order'] > $max_order ? $max_order : $input['order']);
        }

        $platforms = json_decode($input['platforms'], true);
        if ($platforms) {
            $this->addGamePlatforms($game->id, $platforms);
        }

        $servers = json_decode($input['servers'], true);
        if ($servers) {
            $this->addGameServers($game->id, $servers);
        }

        $ranks = json_decode($input['ranks'], true);
        if ($ranks) {
            $this->addGameRanks($game->id, $ranks);
        }

        MasterdataService::clearCacheOneTable('games');

        return $game;
    }

    public function updateGameOrder($gameUpdated, $newOrd)
    {
        $gamesByOrder = Game::orderBy('order', 'asc')->get();
        $isOrderUp = $gameUpdated->order > $newOrd ? true : false;
        $changeOrder = false;
        foreach ($gamesByOrder as $key => $game) {
            $order = $key + 1;

            if ($game->id === $gameUpdated->id) {
                $game->order = $newOrd;
                $game->save();
                if ($isOrderUp) {
                    break;
                } else {
                    $changeOrder = true;
                    continue;
                }
            }

            if ($newOrd == $order) {
                if ($isOrderUp) {
                    $changeOrder = true;
                } else {
                    $game->order = $order - 1;
                    $game->save();
                    break;
                }
            }

            if ($changeOrder) {
                $game->order = $isOrderUp ? $order + 1 : $order - 1;
                $game->save();
            }
        }
    }

    public function deleteGame($id)
    {
        // $existsGameBounty = GameBounty::where('game_id', $id)->exists();
        // if ($existsGameBounty) {
        //     throw new Exception('You cannot delete this game because the game was used for some bounty.');
        // }
        $game = Game::findOrFail($id);
        $this->updateCacheCodeGames(null, $game->code);
        $game->delete();
        return $game;
    }

    public function orderGames()
    {
        $games = Game::withCount('gameProfiles')
            ->orderBy('game_profiles_count', 'desc')
            ->get();

        $gameManualOrd = Game::where('auto_order', Consts::FALSE)->pluck('order')->toArray();
        $index = 0;
        foreach ($games as $key => $game) {
            if (!$game->auto_order) {
                continue;
            }
            $index = $this->getOrderSortingGame($index + 1, $gameManualOrd);

            $game->order = $index;
            $game->save();
        }

        MasterdataService::clearCacheOneTable('games');
        return true;
    }

    private function getOrderSortingGame($index, $listIndex)
    {
        if (in_array($index, $listIndex)) {
            return $this->getOrderSortingGame($index + 1, $listIndex);
        }
        return $index;
    }

    private function updateCacheCodeGames($valueNew = null, $valueOld = null)
    {
        $codes = $this->getCodeGames();
        if($valueOld) {
            foreach ($codes as $index => $code) {
                if($code == $valueOld) {
                    unset($codes[$index]);
                }
            }
        }
        if ($valueNew) {
            $codes->push($valueNew);
        }
        Cache::put($this->key(), $codes);
        return;
    }

    public function getUserTransactions($type, $input)
    {
        $isHistoryTransaction = empty($input['status']) || $input['status'] === 'history';

        $statusHistoryList = [
            Consts::TRANSACTION_STATUS_SUCCESS,
            Consts::TRANSACTION_STATUS_REJECTED,
            Consts::TRANSACTION_STATUS_FAILED,
            Consts::TRANSACTION_STATUS_DENIED,
            Consts::TRANSACTION_STATUS_CANCEL,
        ];

        return Transaction::join('users', 'users.id', 'transactions.user_id')
            ->select('transactions.*', 'users.username as name')
            ->where('transactions.type', $type)
            ->when(
                $isHistoryTransaction,
                function($query) use ($statusHistoryList) {
                    return $query->whereIn('transactions.status', $statusHistoryList);
                },
                function($query) use ($statusHistoryList) {
                    return $query->whereNotIn('transactions.status', $statusHistoryList);
                }
            )
            ->when(
                !empty($input['sort']) && !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                }, function ($query) {
                    $query->orderBy('transactions.created_at', 'desc');
                }
            )
            ->when(!empty($input['search_key']), function ($query) use ($input) {
                $searchKey = $input['search_key'];
                return $query->where(function ($q) use ($searchKey) {
                    return $q->where('users.username', 'like', "%{$searchKey}%")
                            ->orWhere('transactions.transaction_id', 'like', "%{$searchKey}%")
                            ->orWhere('transactions.payment_type', 'like', "%{$searchKey}%")
                            ->orWhere('transactions.status', 'like', "%{$searchKey}%")
                            ->orWhere('transactions.currency', 'like', "%{$searchKey}%");
                });
            })
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getUserBalances($input) 
    {
        return User::join('user_balances', 'user_balances.id', 'users.id')
                ->select('users.id as id', 'users.full_name', 'users.email', 'user_balances.coin', 'user_balances.bar')
                ->when(
                    !empty($input['sort']) && !empty($input['sort_type']),
                    function ($query) use ($input) {
                        $query->orderBy($input['sort'], $input['sort_type']);
                    }, function ($query) {
                        $query->orderBy('id', 'asc');
                    }
                )
                ->when(!empty($input['search_key']), function ($query) use ($input) {
                    $searchKey = $input['search_key'];
                    return $query->where(function ($q) use ($searchKey) {
                        return $q->where('users.email', 'like', "%{$searchKey}%");
                    });
                })
                ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function updateUserBalance($input)
    {

        $userBalance = UserBalance::findOrFail($input['id']);

        if (array_key_exists('coin', $input)) {
            $userBalance->coin = $input['coin'];
        }
        if (array_key_exists('bar', $input)) {
            $userBalance->bar = $input['bar'];
        }

        $userBalance->save();

        MasterdataService::clearCacheOneTable('user_balances');

        return $userBalance;
    }

    public function getUsers($input)
    {
        return User::select('id', 'full_name', 'username', 'email', 'level', 'description', 'status', 'dob', 'sex',
                'user_type', 'is_vip', 'phone_number')
                    ->with('userRanking', 'socialUser')
                    ->when(
                        !empty($input['sort']) && !empty($input['sort_type']),
                        function ($query) use ($input) {
                            $query->orderBy($input['sort'], $input['sort_type']);
                        }, function ($query) {
                            $query->orderBy('id', 'asc');
                        }
                    )
                    ->when(!empty($input['search_key']), function ($query) use ($input) {
                        $searchKey = Utils::escapeLike($input['search_key']);
                        return $query->where(function ($q) use ($searchKey) {
                            $q->where('full_name', 'like', '%' . $searchKey . '%')
                                ->orWhere('email', 'like', '%' . $searchKey . '%')
                                ->orWhere('username', 'like', '%' . $searchKey . '%')
                                ->orWhere('status', 'like', '%' . $searchKey . '%')
                                ->orWhere('phone_number', 'like', '%' . $searchKey . '%')
                                ->orWhereHas('socialUser', function ($query2) use ($searchKey) {
                                     $query2->where('provider', 'like', "%{$searchKey}%");
                                 });
                        });
                    })
                    ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getGamelancerForms($params)
    {
        return GamelancerInfo::join('users', 'gamelancer_infos.user_id', 'users.id')
            ->select('gamelancer_infos.*', 'users.username as username', 'users.email as email', 'users.sex as sex',
                DB::raw("(CASE
                    WHEN gamelancer_infos.status = 'pending'
                    THEN 0
                    WHEN gamelancer_infos.status = 'freegamelancer'
                    THEN 1
                    WHEN gamelancer_infos.status = 'approved'
                    THEN 2
                    ELSE 3 END
                    ) AS sortStatus"
                )
            )
            ->with(['user', 'socialLink'])
            ->when(!empty(array_get($params, 'search_key')), function ($query) use ($params) {
                $keyword = Utils::escapeLike(array_get($params, 'search_key'));
                $query->whereHas('user', function ($query2) use ($keyword) {
                    $query2->where('username', 'LIKE', "%{$keyword}%")
                        ->orWhere('email', 'LIKE', "%{$keyword}%");
                });
            })
            ->when(
                !empty($params['sort']) && !empty($params['sort_type']),
                function ($query) use ($params) {
                    $query->orderBy($params['sort'], $params['sort_type']);
                }, function ($query) {
                    $query->orderBy('sortStatus', 'asc')
                        ->orderBy('updated_at', 'desc');
                }
            )
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function approveGamelancer($id)
    {
        $info = GamelancerInfo::find($id);
        $info->status = Consts::GAMELANCER_INFO_STATUS_APPROVED;
        $info->save();

        $gameProfile = GameProfile::find($info->game_profile_id);
        $gameProfile->is_active = Consts::TRUE;
        $gameProfile->save();

        $user = User::find($info->user_id);
        $user->user_type = Consts::USER_TYPE_PREMIUM_GAMELANCER;
        $user->description = $info->introduction;
        $user->save();

        CalculateGameStatistic::dispatch($gameProfile->game_id, $gameProfile, Consts::GAME_STATISTIC_CREATE_GAME_PROFILE)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);

        event(new UserUpdated($info->user_id));
        event(new UserProfileUpdated($info->user_id));
        event(new GameProfileUpdated($info->game_profile_id));

        Mail::queue(new BecomeGamelancerApprovedMail($user, Consts::TRUE));

        AddKlaviyoMailList::dispatch($user, Consts::KALVIYO_ACTION_UPDATE);

        $notificationParams = [
            'user_id' => $info->user_id,
            'type' => Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
            'message' => Consts::NOTIFY_GAMELANCER_APPROVE,
            'props' => [],
            'data' => [
                'status' => $info->status,
                'user' => (object) ['id' => $user->id]
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        return $info;
    }

    public function approveFreeGamelancer($id)
    {
        $info = GamelancerInfo::find($id);
        $info->status = Consts::GAMELANCER_INFO_STATUS_APPROVED_FREEGAMELANCER;
        $info->save();

        $gameProfile = GameProfile::find($info->game_profile_id);
        $gameProfile->is_active = Consts::TRUE;
        $gameProfile->save();

        GameProfileOffer::where('game_profile_id', $info->game_profile_id)->update(['price' => 0]);

        $user = User::find($info->user_id);
        $user->user_type = Consts::USER_TYPE_FREE_GAMELANCER;
        $user->description = $info->introduction;
        $user->save();

        CalculateGameStatistic::dispatch($gameProfile->game_id, $gameProfile, Consts::GAME_STATISTIC_CREATE_GAME_PROFILE)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);

        event(new UserUpdated($info->user_id));
        event(new UserProfileUpdated($info->user_id));
        event(new GameProfileUpdated($info->game_profile_id));

        Mail::queue(new BecomeGamelancerApprovedFreeGamelancerMail($user, $gameProfile->game->slug));

        AddKlaviyoMailList::dispatch($user, Consts::KALVIYO_ACTION_UPDATE);

        $notificationParams = [
            'user_id' => $info->user_id,
            'type' => Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
            'message' => Consts::NOTIFY_GAMELANCER_APPROVE_FREEGAMELANCER,
            'props' => [],
            'data' => [
                'status' => $info->status,
                'user' => (object) ['id' => $user->id]
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        return $info;
    }

    public function disapproveGamelancer($id)
    {
        $info = GamelancerInfo::find($id);
        $info->status = Consts::GAMELANCER_INFO_STATUS_REJECTED;
        $info->save();

        GamelancerAvailableTime::where('user_id', $info->user_id)->delete();
        UserSocialNetwork::where('id', $info->social_link_id)->delete();

        Mail::queue(new BecomeGamelancerRejectedMail($info));

        $notificationParams = [
            'user_id' => $info->user_id,
            'type' => Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
            'message' => Consts::NOTIFY_GAMELANCER_REJECT,
            'props' => [],
            'data' => [
                'status' => $info->status
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        return $info;
    }

    public function getInvitationCodes($params)
    {
        return InvitationCode::paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function updateUser($input)
    {
        if (!in_array($input['status'], [Consts::USER_ACTIVE, Consts::USER_INACTIVE])) {
            throw new Exception('Status user is invalid.');
        }

        $user = User::findOrFail($input['id']);
        $userType = empty($input['user_type']) ? null : intval($input['user_type']);
        $userCurrentType = $user->user_type;

        $isChangeUserType = $userCurrentType !== $userType && !empty($userType);
        $isDowngradeUser = false;
        $isUpgradeUser = false;

        if ($isChangeUserType) {
            switch ($userType) {
                case Consts::USER_TYPE_PREMIUM_GAMELANCER:
                    $isUpgradeUser = true;
                    break;
                case Consts::USER_TYPE_USER:
                    $isDowngradeUser = true;
                    break;
                case Consts::USER_TYPE_FREE_GAMELANCER:
                    if ($userCurrentType === Consts::USER_TYPE_USER) {
                        $isUpgradeUser = true;
                    }
                    if ($userCurrentType === Consts::USER_TYPE_PREMIUM_GAMELANCER) {
                        $isDowngradeUser = true;
                    }
                    break;
                default:
                    break;
            }
        }

        // $isGamelancer = empty($input['user_type']) ? Consts::FALSE : Consts::TRUE;
        // // downgrade gamelancer to user
        // $isDownGradeUser = $user->user_type && !$isGamelancer;
        // if ($isDownGradeUser) {
        //     throw new Exception('Cannot downgrade Gamelancer to normal User!');
        // }

        $status = $user->status;
        $user->level = $input['level'];
        $user->status = $input['status'];
        $user->user_type = empty($userType) ? $user->user_type : $input['user_type'];
        $user->is_vip = empty($input['is_vip']) ? Consts::FALSE : Consts::TRUE;
        $user->save();

        if($status != $input['status']) {
            Mail::queue(new ChangeUserStatusMailQueue($user->email, $user->status, Consts::DEFAULT_LOCALE, $user->username));
            if ($input['status'] === Consts::USER_INACTIVE) {
                $this->revokeUserTokens($user);

                event(new UserSessionTerminated($user->id, [
                    'user_id' => $user->id,
                    'terminated' => Consts::TRUE
                ]));
            }
        }

        if ($isChangeUserType) {
            if (intval($user->user_type) === Consts::USER_TYPE_FREE_GAMELANCER) {
                Mail::queue(new BecomeGamelancerApprovedFreeGamelancerMail($user));

                $notificationParams = [
                    'user_id' => $user->id,
                    'type' => Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
                    'message' => Consts::NOTIFY_GAMELANCER_APPROVE_FREEGAMELANCER,
                    'props' => [],
                    'data' => [
                        'status' => Consts::GAMELANCER_INFO_STATUS_APPROVED_FREEGAMELANCER,
                        'user' => (object) ['id' => $user->id]
                    ]
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
            }

            if (intval($user->user_type) === Consts::USER_TYPE_PREMIUM_GAMELANCER) {
                $upgradeFromFreeGamelancer = $userCurrentType === Consts::USER_TYPE_FREE_GAMELANCER;
                Mail::queue(new BecomeGamelancerApprovedMail($user, Consts::TRUE));

                $notificationParams = [
                    'user_id' => $user->id,
                    'type' => Consts::NOTIFY_TYPE_CONFIRM_GAMELANCER,
                    'message' => $upgradeFromFreeGamelancer ? Consts::NOTIFY_GAMELANCER_APPROVE_FROM_FREE : Consts::NOTIFY_GAMELANCER_APPROVE,
                    'props' => [],
                    'data' => [
                        'status' => Consts::GAMELANCER_INFO_STATUS_APPROVED,
                        'user' => (object) ['id' => $user->id]
                    ]
                ];
                $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);
            }
        }
        event(new UserUpdated($user->id));
        AddKlaviyoMailList::dispatch($user, Consts::KALVIYO_ACTION_UPDATE);

        return $user;
    }

    private function revokeUserTokens($user)
    {
        if ($user->status === Consts::USER_ACTIVE) {
            return;
        }

        return DB::table('oauth_access_tokens')->where('user_id', $user->id)
            ->update([
                'revoked' => Consts::TRUE,
                'mattermost_token' => null
            ]);
    }

    public function getPlatforms($input)
    {
        return Platform::select('id', 'name')
            ->when(
                !empty($input['sort']) && !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                }, function ($query) {
                    $query->orderBy('id', 'asc');
                }
            )
            ->when(!empty($input['search_key']), function ($query) use ($input) {
                $searchKey = $input['search_key'];
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('name', 'like', '%' . $searchKey . '%');
                });
            })
            ->get();
    }

    public function createNewPlatform($input)
    {
        $image = $input['icon'];
        $imgPath = Utils::saveFileToStorage($image, 'icon');
        $platform = Platform::create([
            'name' => $input['name'],
            'icon' => $imgPath,
            'code' => $input['code'],
        ]);

        MasterdataService::clearCacheOneTable('platforms');
        event(new MasterdataUpdated());

        return $platform;
    }

    public function updatePlatform($input)
    {
        $imgPath = null;
        if ($input->icon) {
            $imgPath = Utils::saveFileToStorage($input->icon, 'icon');
        }
        $platform = Platform::findOrFail($input->id);
        $platform->name = $input->name;
        $platform->icon = $imgPath ? $imgPath : $platform->icon;
        $platform->code = $input->code;

        $platform->save();

        MasterdataService::clearCacheOneTable('platforms');
        event(new MasterdataUpdated());

        return $platform;
    }

    public function removePlatform($input)
    {
        // $existsPlatform = GameBounty::where('platform_id', $input['id'])->exists();
        // if ($existsPlatform) {
        //     throw new Exception('You cannot delete this platform because the platform was used for some bounty.');
        // }
        $platform = Platform::findOrFail($input['id'])->delete();

        MasterdataService::clearCacheOneTable('platforms');
        event(new MasterdataUpdated());

        return $platform;
    }

    public function getOffers($input)
    {
        return Offer::select('id', 'coin', 'cover', 'price', 'bonus', 'always_bonus')
            ->when(
                !empty($input['sort']) && !empty($input['sort_type']),
                function ($query) use ($input) {
                    $query->orderBy($input['sort'], $input['sort_type']);
                }, function ($query) {
                    $query->orderBy('id', 'asc');
                }
            )
            ->when(!empty($input['search_key']), function ($query) use ($input) {
                $searchKey = $input['search_key'];
                return $query->where(function ($q) use ($searchKey) {
                    $q->where('coin', 'like', '%' . $searchKey . '%')
                    ->orWhere('price', 'like', '%' . $searchKey . '%')
                    ->orWhere('bonus', 'like', '%' . $searchKey . '%')
                    ->orWhere('always_bonus', 'like', '%' . $searchKey . '%');
                });
            })
            ->when(
                empty($input['limit']),
                function ($query) {
                    return $query->get();
                },
                function ($query) use ($input) {
                    return $query->paginate($input['limit']);
                }
            );
    }

    public function createOffer($input)
    {
        $image = $input['cover'];
        $imgPath = Utils::saveFileToStorage($image, 'cover');
        $offer = Offer::create([
            'coin' => $input['coin'],
            'cover' => $imgPath,
            'price' => $input['price'],
            'bonus' => $input['bonus'],
            'always_bonus' => $input['always_bonus']
        ]);

        MasterdataService::clearCacheOneTable('offers');

        return $offer;
    }

    public function updateOffer($input)
    {
        $imgPath = null;
        if ($input->cover) {
            $imgPath = Utils::saveFileToStorage($input->cover, 'cover');
        }
        $offer = Offer::findOrFail($input->id);
        $offer->coin = $input->coin;
        $offer->price = $input->price;
        $offer->bonus = $input->bonus;
        $offer->cover = $imgPath ? $imgPath : $offer->cover;
        $offer->always_bonus = $input->always_bonus;

        $offer->save();

        MasterdataService::clearCacheOneTable('offers');

        return $offer;
    }

    public function removeOffer($input)
    {
        $existsPendingOffers = Transaction::where('offer_id', $input['id'])->where('status', Consts::TRANSACTION_STATUS_PENDING)->exists();
        if ($existsPendingOffers) {
            throw new Exception('You cannot delete this offer because this offer was used for some transactions.');
        }

        $offer = Offer::findOrFail($input['id'])->delete();

        MasterdataService::clearCacheOneTable('offers');

        return $offer;
    }

    public function getBounties($input)
    {
        return Bounty::with(['claimBountyRequest', 'user', 'game'])
                        ->when(
                        !empty($input['sort']) && !empty($input['sort_type']),
                        function ($query) use ($input) {
                            $query->orderBy($input['sort'], $input['sort_type']);
                        }, function ($query) {
                            $query->orderBy('updated_at', 'desc');
                        }
                    )
                    ->when(!empty($input['search_key']), function ($query) use ($input) {
                        $searchKey = $input['search_key'];
                        return $query->where(function ($q) use ($searchKey) {
                            $q->where('description', 'like', "%{$searchKey}%")
                                ->orWhere('title', 'like', "%{$searchKey}%")
                                ->orWhere('status', "%{$searchKey}%");
                        });
                    })
                    ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getBountyClaimRequest($input)
    {
        return BountyClaimRequest::with(['claimerInfo'])
                    ->where('bounty_id', $input['id'])
                    ->when(
                        !empty($input['sort']) && !empty($input['sort_type']),
                        function ($query) use ($input) {
                            $query->orderBy($input['sort'], $input['sort_type']);
                        }, function ($query) {
                            $query->orderBy('updated_at', 'desc');
                        }
                    )
                    ->when(!empty($input['search_key']), function ($query) use ($input) {
                        $searchKey = $input['search_key'];
                        return $query->where(function ($q) use ($searchKey) {
                            $q->where('description', 'like', "%{$searchKey}%")
                                ->orWhere('status', "%{$searchKey}%");
                        });
                    })
                    ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getSessions($input)
    {
        $searchKey = array_get($input, 'search_key');
        $searchType = array_get($input, 'search_type');
        $sortType = array_get($input, 'sort_type');
        $sortBy = array_get($input, 'sort');
        $filterGame = array_get($input, 'filter_game');
        $filterStatus = array_get($input, 'filter_status');

        return Session::join('game_profile_offers', 'game_profile_offers.id', 'sessions.game_profile_id')
            ->join('users as gamelancer', 'gamelancer.id', 'sessions.gamelancer_id')
            ->join('users as claimer', 'claimer.id', 'sessions.claimer_id')
            ->join('game_profiles', 'game_profiles.id', 'sessions.game_profile_id')
            ->join('games', 'games.id', 'game_profiles.game_id')
            ->select('sessions.id', 'sessions.quantity', 'sessions.status', 'game_profile_offers.price', 'game_profile_offers.type', 'gamelancer.username as gamelancer_name', 'claimer.username as claimer_name', 'games.title as game_title')
            ->when(!empty($filterGame), function ($query) use ($filterGame) {
                $query->where('games.slug', $filterGame);
            })
            ->when(!empty($filterStatus), function ($query) use ($filterStatus) {
                $query->where('sessions.status', $filterStatus);
            })
            ->when(!empty($sortBy) && !empty($sortType),
                function ($query) use ($sortBy, $sortType) {
                    $query->orderBy($sortBy, $sortType);
                },
                function ($query) {
                    $query->orderBy('sessions.updated_at', 'desc');
                }
            )
            ->when(!empty($searchKey) && !empty($searchType),function ($query) use ($searchKey, $searchType) {
                $searchKey = Utils::escapeLike($searchKey);
                if ($searchType === 'all') {
                    return $query->where(function ($query2) use ($searchKey) {
                        $query2->where('games.title', 'like', '%' . $searchKey . '%')
                            ->orWhere('gamelancer.username', 'like', '%' . $searchKey . '%')
                            ->orWhere('claimer.username', 'like', '%' . $searchKey . '%');
                    });
                }

                return $query->where($searchType, 'like', '%' . $searchKey . '%');
            })
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getSessionDetail($sessionId)
    {
        return Session::with(['gameProfile', 'gamelancerInfo', 'claimerInfo', 'gameOffer', 'reason'])
            ->where('id', $sessionId)
            ->first();
    }

    public function getSiteSettings($input)
    {
        $booleanValues = [
            Consts::VIP_SETTING,
            Consts::VISIBLE_BOUNTY_KEY
        ];

        return Setting::select('key', 'value')
            ->whereIn('key', Consts::KEY_SETTINGS_ADMIN)
            ->get()
            ->keyBy('key')
            ->transform(function ($item) use ($booleanValues) {
                if (in_array($item->key, $booleanValues)) {
                    return $item->value == 1 ? true : false;
                }
                return $item->value;
            });
    }

    public function updateSiteSettings($input)
    {
        foreach ($input as $key => $value) {
            $query = DB::table('settings')->where('key', $key);

            if ($query->exists()) {
                $query->update([
                    'value' => $value,
                    'updated_at' => now()
                ]);
                continue;
            }

            DB::table('settings')->insert([
                'key' => $key,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        MasterdataService::clearCacheOneTable('settings');
    }

    public function getBanners($input)
    {
        return Banner::paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function updateBanner($input)
    {
        $thumbnail = @file_exists($input['thumbnail']) ? Utils::saveFileToStorage($input['thumbnail'], 'data/b') : $input['thumbnail'];

        $banner = Banner::find($input['id']);
        $banner->thumbnail = $thumbnail;
        $banner->link = array_get($input, 'link');
        $banner->title = array_get($input, 'title');
        $banner->description = array_get($input, 'description');
        $banner->btn_caption = array_get($input, 'btn_caption');
        $banner->type = array_get($input, 'type');
        $banner->is_active = $input['is_active'] ? 1 : 0;
        $banner->save();

        MasterdataService::clearCacheOneTable('banners');
        return $banner;
    }

    public function createBanner($input)
    {
        $thumbnail = Utils::saveFileToStorage($input['thumbnail'], 'data/b');

        $banner = Banner::create([
            'thumbnail' => $thumbnail,
            'link' => array_get($input, 'link'),
            'title' => array_get($input, 'title'),
            'description' => array_get($input, 'description'),
            'btn_caption' => array_get($input, 'btn_caption'),
            'type' => array_get($input, 'type'),
            'is_active' => $input['is_active'] ? 1 : 0
        ]);

        MasterdataService::clearCacheOneTable('banners');
        return $banner;
    }

    public function deleteBanner($input)
    {
        $deleteBanner = Banner::where('id', $input['id'])->delete();
        MasterdataService::clearCacheOneTable('banners');
        return $deleteBanner;
    }

    public function getReviews($input)
    {
        $searchKey = array_get($input, 'search_key');
        $searchType = array_get($input, 'search_type');
        $sortType = array_get($input, 'sort_type');
        $sortBy = array_get($input, 'sort');
        $filterGame = array_get($input, 'filter_game');
        $objectType = array_get($input, 'object_type');

        return SessionReview::with(['tags'])
            ->join('users', 'users.id', 'session_reviews.user_id')
            ->join('users as reviewer', 'reviewer.id', 'session_reviews.reviewer_id')
            ->when(!empty($objectType) && $objectType === Consts::OBJECT_TYPE_SESSION, function ($query) {
                $query->join('sessions', 'sessions.id', 'session_reviews.object_id')
                    ->join('game_profiles', 'game_profiles.id', 'sessions.game_profile_id')
                    ->join('users as gamelancer', 'gamelancer.id', 'game_profiles.user_id')
                    ->join('games', 'games.id', 'game_profiles.game_id');
            })
            ->when(!empty($objectType) && $objectType === Consts::OBJECT_TYPE_BOUNTY, function ($query) {
                $query->join('bounties', 'bounties.id', 'session_reviews.object_id')
                    ->join('games', 'games.id', 'bounties.game_id');
            })
            ->select('session_reviews.id', 'users.username', 'reviewer.username as reviewer_name', 'session_reviews.rate', 'session_reviews.description', 'games.title as game_title', 'gamelancer.username as gamelancer_name')
            ->where('object_type', $objectType)
            ->when(!empty($filterGame), function ($query) use ($filterGame) {
                $query->where('games.slug', $filterGame);
            })
            ->when(!empty($searchKey) && !empty($searchType),function ($query) use ($searchKey, $searchType) {
                $searchKey = Utils::escapeLike($searchKey);
                if ($searchType === 'all') {
                    return $query->where(function ($query2) use ($searchKey) {
                        $query2->where('users.username', 'like', '%' . $searchKey . '%')
                            ->orWhere('reviewer.username', 'like', '%' . $searchKey . '%');
                    });
                }

                return $query->where($searchType, 'like', '%' . $searchKey . '%');
            })
            ->when(!empty($sortBy) && !empty($sortType),
                function ($query) use ($sortBy, $sortType) {
                    $query->orderBy($sortBy, $sortType);
                },
                function ($query) {
                    $query->orderBy('session_reviews.created_at', 'desc');
                }
            )
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function deleteReview($id)
    {
        $sessionReview = SessionReview::find($id);
        $sessionReview->delete();

        CalculateUserRatingWhenRemoveReview::dispatchNow($sessionReview->user_id, $sessionReview);

        return 'ok';
    }

    public function getGamelancerInfoDetail($id)
    {
        return GamelancerInfo::with(['user', 'socialLink', 'gameProfile'])
            ->where('id', $id)
            ->first();
    }

    public function getUsernames($input)
    {
        $searchKey = Utils::escapeLike(array_get($input, 'search_key', ''));
        return User::select('id', 'username')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                $query->where('username', 'like', "%{$searchKey}%");
            })
            ->get();
    }

    public function getUserRestrictPricings($input)
    {
        $searchKey = Utils::escapeLike(array_get($input, 'search_key', ''));

        return UserRestrictPricing::join('users', 'users.id', 'user_restrict_pricings.user_id')
            ->select('user_restrict_pricings.*', 'users.username')
            ->when(!empty($searchKey), function ($query) use ($searchKey) {
                $query->where('users.username', 'like', "%{$searchKey}%");
            })
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function createUserRestrictPricing($input)
    {
        $username = array_get($input, 'username');
        $min = array_get($input, 'min');
        $max = array_get($input, 'max');

        $user = User::select('id')->where('username', $username)->first();

        if (UserRestrictPricing::userRestrictPricingExists($user->id)) {
            throw ValidationException::withMessages([
                'username' => [__('exceptions.user_restrict_pricing_exists')]
            ]);
        }

        $userRestrictPricing = UserRestrictPricing::create([
            'user_id' => $user->id,
            'min' => $min,
            'max' => $max
        ]);

        return $userRestrictPricing;
    }

    public function updateUserRestrictPricing($input)
    {
        $id = array_get($input, 'id');
        $min = array_get($input, 'min');
        $max = array_get($input, 'max');

        $userRestrictPricing = UserRestrictPricing::find($id);
        $userRestrictPricing->min = $min;
        $userRestrictPricing->max = $max;
        $userRestrictPricing->save();

        return $userRestrictPricing;
    }

    public function deleteUserRestrictPricing($id)
    {
        $userRestrictPricing = UserRestrictPricing::find($id);
        $userRestrictPricing->delete();

        return 'ok';
    }

    public function getRankings($input)
    {
        return Ranking::orderBy('exp')
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function createRanking($input)
    {
        $url = Utils::saveFileToStorage($input['url'], 'data/b');

        $ranking = Ranking::create([
            'url' => $url,
            'name' => array_get($input, 'name'),
            'code' => Str::slug(array_get($input, 'name')),
            'exp' => array_get($input, 'exp'),
            'order' => 1,
            'threshold_exp_in_day' => array_get($input, 'threshold_exp_in_day')
        ]);

        $this->updateRankingOrder();

        MasterdataService::clearCacheOneTable('rankings');
        return $ranking;
    }

    public function updateRanking($input)
    {
        $url = @file_exists($input['url']) ? Utils::saveFileToStorage($input['url'], 'data/b') : $input['url'];

        $ranking = Ranking::find($input['id']);
        $ranking->url = $url;
        $ranking->name = array_get($input, 'name');
        $ranking->code = Str::slug($ranking->name);
        $ranking->exp = array_get($input, 'exp');
        $ranking->threshold_exp_in_day = array_get($input, 'threshold_exp_in_day');
        $ranking->save();

        $this->updateRankingOrder();

        MasterdataService::clearCacheOneTable('rankings');
        return $ranking;
    }

    public function updateRankingOrder() {
        $ranks = Ranking::orderBy('exp', 'asc')->get();
        foreach ($ranks as $key => $rank) {
            $rank->order = $key + 1;
            $rank->save();
        }
    }

    public function deleteRanking($input)
    {
        $deleteRanking = Ranking::where('id', $input['id'])->delete();
        $this->updateRankingOrder();
        MasterdataService::clearCacheOneTable('rankings');
        return $deleteRanking;
    }

    public function getRewards($input)
    {
        return TaskingReward::orderBy('type')->orderBy('level')
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function createReward($input)
    {
        $taskingReward = TaskingReward::create([
            'type' => array_get($input, 'type'),
            'level' => array_get($input, 'level'),
            'quantity' => array_get($input, 'quantity'),
            'currency' => array_get($input, 'currency'),
        ]);

        return $taskingReward;
    }

    public function updateReward($input)
    {
        $taskingReward = TaskingReward::find($input['id']);
        $taskingReward->type = array_get($input, 'type');
        $taskingReward->level = array_get($input, 'level');
        $taskingReward->quantity = array_get($input, 'quantity');
        $taskingReward->currency = array_get($input, 'currency');
        $taskingReward->save();

        return $taskingReward;
    }

    public function deleteReward($input)
    {
        $deleteReward = TaskingReward::where('id', $input['id'])->delete();
        return $deleteReward;
    }

    public function getTaskings($input)
    {
        return Tasking::orderBy('type')->orderBy('order')->orderBy('exp')
            ->paginate(array_get($input, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function createTasking($input)
    {
        $url = $input['url'] ? Utils::saveFileToStorage($input['url'], 'data/b') : null;

        $tasking = Tasking::create([
            'type'                  => array_get($input, 'type'),
            'order'                 => array_get($input, 'order'),
            'title'                 => array_get($input, 'title'),
            'code'                  => Str::slug($input['title']),
            'exp'                   => array_get($input, 'exp'),
            'description'           => array_get($input, 'description'),
            'bonus_value'           => array_get($input, 'bonus_value'),
            'bonus_currency'        => array_get($input, 'bonus_currency'),
            'threshold_exp_in_day'  => array_get($input, 'threshold_exp_in_day'),
            'url'                   => $url
        ]);

        MasterdataService::clearCacheOneTable('taskings');
        return $tasking;
    }

    public function updateTasking($input)
    {
        $url = @file_exists($input['url']) ? Utils::saveFileToStorage($input['url'], 'data/b') : $input['url'];

        $tasking = Tasking::find($input['id']);
        $tasking->url = $url;
        $tasking->type = array_get($input, 'type');
        $tasking->order = array_get($input, 'order');
        $tasking->description = array_get($input, 'description');
        $tasking->title = array_get($input, 'title');
        $tasking->short_title = array_get($input, 'short_title');
        $tasking->short_description = array_get($input, 'short_description');
        $tasking->exp = array_get($input, 'exp');
        $tasking->threshold_exp_in_day = array_get($input, 'threshold_exp_in_day');
        $tasking->bonus_value = array_get($input, 'bonus_value');
        $tasking->bonus_currency = array_get($input, 'bonus_currency');
        $tasking->save();

        MasterdataService::clearCacheOneTable('taskings');
        return $tasking;
    }

    // public function updateTaskingOrder() {
    //     $ranks = Tasking::orderBy('exp', 'asc')->get();
    //     foreach ($ranks as $key => $rank) {
    //         $rank->order = $key + 1;
    //         $rank->save();
    //     }
    // }

    public function deleteTasking($input)
    {
        $deleteRanking = Tasking::where('id', $input['id'])->delete();
        MasterdataService::clearCacheOneTable('taskings');
        return $deleteRanking;
    }

    public function getDailyCheckinPoints($input)
    {
        return ExperiencePoint::orderBy('day')->get();
    }

    public function updateDailyCheckinPoint($data)
    {
        $point = ExperiencePoint::firstOrNew(['day' => array_get($data, 'day')]);
        $point->exp = array_get($data, 'exp');
        $point->save();

        return $point;
    }

    public function updateMultipleDailyCheckinPoints ($input) {
        $this->deleteDailyCheckinPoint($input);
        foreach($input as $point) {
            $this->updateDailyCheckinPoint($point);
        }
        // MasterdataService::clearCacheOneTable('experience_point');
    }

    public function deleteDailyCheckinPoint($input)
    {
        ExperiencePoint::withTrashed()->where('day', '<=', count($input))->restore();
        return ExperiencePoint::where('day', '>', count($input))->delete();
    }

    public function getDailyCheckinPeriod()
    {
        return Setting::where('key', Consts::DAILY_CHECKIN_PERIOD_KEY)->value('value');
    }

    public function updateDailyCheckinPeriod($input)
    {
        $setting = Setting::firstOrCreate([
            'key' => Consts::DAILY_CHECKIN_PERIOD_KEY
        ]);
        $setting->value = array_get($input, 'period', 0);
        $setting->save();

        return $setting;
    }
}
