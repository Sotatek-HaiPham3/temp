<?php

namespace App\Http\Services;

use App\Consts;
use App\Utils;
use App\Utils\BigNumber;
use App\Utils\GameStatisticUtils;
use App\Utils\UserOnlineUtils;
use App\Utils\ChatUtils;
use App\Utils\BuilderUtils;
use App\Models\Game;
use App\Models\GameProfile;
use App\Models\GameProfileOffer;
use App\Models\GameProfileMatchServer;
use App\Models\GameProfileMedia;
use App\Models\SessionReview;
use App\Models\UserFollowing;
use App\Models\User;
use App\Models\GameProfilePlatform;
use App\Models\UserRestrictPricing;
use App\Models\Tasking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Auth;
use Exception;
use Mail;
use Cache;
use App\Mails\GameProfileOnlineMail;
use App\Mails\NewGameProfileMail;
use App\Events\GameProfileUpdated;
use App\Events\GameProfileDeletedUpdated;
use App\Events\GameProfileCollection;
use App\Traits\NotificationTrait;
use App\Exceptions\Reports\GameProfileException;
use Illuminate\Validation\ValidationException;
use App\Jobs\CalculateGameStatistic;
use App\Jobs\CollectTaskingJob;
use App\Http\Services\MasterdataService;

class GameProfileService extends BaseService
{
    use NotificationTrait;

    const USER_MATCHING_TIME_LIVE               = 300; // 5 minutes
    const USER_MATCHING_COLLECTION_TIME_LIVE    = 600; // 10 minutes

    private function getGameProfilesBuilder($params)
    {
        $searchKey  = array_get($params, 'search_key');
        $sortBy     = array_get($params, 'sortBy');
        $type       = array_get($params, 'type');

        $following = [];
        if ($type === Consts::GAME_PROFILE_LIST_TYPE_FOLLOWING) {
            $following = UserFollowing::where('user_id', array_get($params, 'user_id_following'))
                ->where('is_following', Consts::TRUE)
                ->pluck('following_id');
        }

        return GameProfile::join('users', 'game_profiles.user_id', 'users.id')
            ->leftJoin('user_statistics', 'game_profiles.user_id', 'user_statistics.user_id')
            ->select('game_profiles.*', 'user_statistics.total_followers', 'user_statistics.rating', 'users.created_at as newest_user')
            ->with([
                'gameOffers',
                'user' => function ($query) {
                    User::withoutAppends();
                },
                'medias',
                'platforms',
                'game' => function ($query) {
                    $query->select('id', 'thumbnail');
                },
                'statistic',
                'userRanking' => function ($query) {
                    $query->select('id', 'user_id', 'ranking_id');
                },
            ])
            ->where('game_profiles.is_active', Consts::TRUE)
            ->when(!empty(array_get($params, 'game_id')), function ($query) use ($params) {
                $query->where('game_profiles.game_id', array_get($params, 'game_id'));
            })
            ->when(!empty(array_get($params, 'user_id')), function ($query) use ($params) {
                $query->where('game_profiles.user_id', array_get($params, 'user_id'));
            })
            ->when(!empty(array_get($params, 'game_profile_id')), function ($query) use ($params) {
                $query->where('game_profiles.id', array_get($params, 'game_profile_id'));
            })
            ->when(!empty(array_get($params, 'not_id')), function ($query) use ($params) {
                $query->where('game_profiles.id', '!=', array_get($params, 'not_id'));
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
                $query->whereHas('platforms', function ($query2) use ($params) {
                    $query2->where('platform_id', array_get($params, 'platform'));
                });
            })
            ->when(array_key_exists('price', $params) && !is_null($params['price']), function ($query) use ($params) {
                $searchKey = array_get($params, 'price');
                list($source, $target) = explode(Consts::CHAR_UNDERSCORE, $searchKey);

                $isThresholdUpperLimit = BigNumber::new($target)->comp(0);
                if (!$isThresholdUpperLimit) {
                    return $query->where(function ($query2) use ($source, $target) {
                        $query2->whereDoesntHave('gameOffers')
                            ->orWhereHas('gameOffers', function ($query3) use ($source, $target) {
                                $query3->where('price', '>=', $source)
                                    ->when(!is_null($target), function ($query4) use ($target) {
                                        $query4->where('price', '<=', $target);
                                    });
                            });
                    });
                }

                $query->whereHas('gameOffers', function ($query2) use ($source, $target) {
                    $query2->where('price', '>=', $source)
                        ->when(!is_null($target), function ($query3) use ($target) {
                            $query3->where('price', '<=', $target);
                        });
                });
            })
            ->when(array_key_exists('offer_type', $params) && !is_null($params['offer_type']), function ($query) use ($params) {
                $query->whereHas('gameOffers', function ($query2) use ($params) {
                    $query2->where('type', $params['offer_type']);
                });
            })
            ->when(array_key_exists('user_type', $params) && !is_null($params['user_type']), function ($query) use ($params) {
                $query->whereHas('user', function ($query2) use ($params) {
                    $query2->where('user_type', array_get($params, 'user_type'));
                });
            })
            ->when(array_key_exists('online', $params) && !is_null($params['online']), function ($query) use ($params) {
                $userIdOnlines = UserOnlineUtils::getUserIdOnlines();

                if ($params['online']) {
                    return $query->whereIn('game_profiles.user_id', $userIdOnlines);
                }

                return $query->whereNotIn('game_profiles.user_id', $userIdOnlines);
            })
            // ->when(!empty(array_get($params, 'level')), function ($query) use ($params) {
            //     $query->whereHas('user', function ($query2) use ($params) {
            //         $query2->where('level', array_get($params, 'level'));
            //     });
            // })
            // ->when(!empty(array_get($params, 'region')), function ($query) use ($params) {
            //     $query->whereHas('matchServers', function ($query2) use ($params) {
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
            ->when(!empty($type), function ($query) use ($type, $following) {
                switch ($type) {
                    case Consts::GAME_PROFILE_LIST_TYPE_FOLLOWING:
                        $query->whereIn('game_profiles.user_id', $following);
                        break;
                    case Consts::GAME_PROFILE_LIST_TYPE_LAST:
                        $query->orderBy('newest_user', 'desc')
                            ->orderBy('updated_at', 'desc');
                        break;
                    case Consts::GAME_PROFILE_LIST_TYPE_TOP:
                        $query->orderBy('total_followers', 'desc')
                            ->orderBy('rating', 'desc');
                        break;
                    case Consts::GAME_PROFILE_LIST_TYPE_TOP_FOLLOWERS:
                        $query->orderBy('total_followers', 'desc');
                        break;
                    case Consts::GAME_PROFILE_LIST_TYPE_TOP_RATED:
                        $query->orderBy('rating', 'desc');
                        break;
                    default:
                        break;
                }
            })
            ->when(!empty($sortBy), function ($query) use ($sortBy) {
                switch ($sortBy) {
                    case Consts::SORT_BY_FOLLOWER:
                        $query->orderBy('total_followers', 'desc');
                        break;
                    case Consts::SORT_BY_REVIEW:
                        $query->orderBy('rating', 'desc');
                        break;
                    case Consts::SORT_BY_NEWEST:
                        $query->orderBy('newest_user', 'desc')
                            ->orderBy('updated_at', 'desc');
                        break;
                    default:
                        break;
                }
            });
    }

    public function getAllGameProfiles($params)
    {
        return $this->getGameProfilesBuilder($params)
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getFeaturedGamelancers($params)
    {
        // Hotfix
        $data = GameProfile::join('users', 'game_profiles.user_id', 'users.id')
            ->join('user_statistics', 'game_profiles.user_id', 'user_statistics.user_id')
            ->select('game_profiles.*', 'user_statistics.total_followers', 'user_statistics.rating',
                'users.created_at as newest_user')
            ->with([
                'gameOffers',
                'user' => function ($query) {
                    User::withoutAppends();
                },
                'medias',
                'platforms',
                'game'
            ])
            ->where('is_active', Consts::TRUE)
            ->orderBy('rating', 'desc')
            ->take(100)
            ->get();

        $result = collect([]);

        foreach ($data as $key => $value) {
            $exists = $result->contains(function ($item) use ($value) {
                return $item->user_id === $value->user_id;
            });

            if ($exists) {
                continue;
            }

            $result->push($value);
        }

        $limit = array_get($params, 'limit', Consts::DEFAULT_PER_PAGE);
        return $result->take($limit);
    }

    public function getGameProfileDetail($params = [])
    {
        $username = array_get($params, 'username');
        $gameSlug = array_get($params, 'slug');
        $limitMedias = array_get($params, 'limit_medias', Consts::DEFAULT_PER_PAGE);

        $gameProfile = GameProfile::with([
                'gameOffers',
                // 'matchServers',
                'medias',
                'game',
                'platforms',
                'userSocialLink',
                'availableTimes',
                'user',
                'userPhotos' => function ($subQuery) {
                    return $subQuery->where('type', Consts::USER_MEDIA_PHOTO)
                        ->orderBy('created_at', 'desc');
                },
                'statistic'
            ])
            ->whereHas('user', function ($query) use ($username) {
                $query->where('username', $username);
            })
            ->whereHas('game', function ($query) use ($gameSlug) {
                $query->where('slug', $gameSlug);
            })
            ->where('is_active', Consts::TRUE)
            ->first();

        if (!$gameProfile) {
            return [];
        }

        $personality = [];
        $gameProfile->user->personality->groupBy('review_tag_id')
            ->each(function ($item) use (&$personality) {
                $res = $item->first();
                $res->quantity = $item->sum('quantity');
                unset($res->review_type);
                $personality[] = $res;
            });

        $gameProfile->user->tagStatistic = $personality;
        unset($gameProfile->user->personality);

        $gameProfile->userPhotos->transform(function ($item) {
            $item->belongsto = Consts::USER_PHOTO;
            return $item;
        });

        $medias = collect($gameProfile->medias)->transform(function ($item) {
                $item->belongsto = Consts::GAME_PROFILE_MEDIA;
                return $item;
            })
            ->concat($gameProfile->userPhotos)
            ->take($limitMedias)
            ->toArray();

        $gameProfile->unsetRelation('userPhotos');
        $gameProfile->unsetRelation('medias');

        $gameProfile['medias'] = $medias;

        $reviews = SessionReview::select('id', 'game_profile_id', 'reviewer_id', 'user_id', 'rate', 'description', 'recommend', 'created_at')
            ->with(['userReview', 'tags'])
            ->where('game_profile_id', $gameProfile->id)
            ->where('user_id', $gameProfile->user_id)
            ->where('object_type', Consts::OBJECT_TYPE_SESSION)
            ->orderBy('id', 'desc')
            ->get();

        $gameProfile->reviews = $reviews;
        return $gameProfile;
    }

    public function createGameProfileFromBecomeGamelancer($params, $isActive = Consts::TRUE)
    {
        $gameProfileExisted = GameProfile::where('user_id', Auth::id())
            ->where('game_id', array_get($params, 'game_id'))
            ->first();
        if (!$gameProfileExisted) {
            return $this->createGameProfile($params, $isActive);
        }

        $gameProfile = $this->updateGameProfile($gameProfileExisted->id, $params, $isActive);
        if (!empty(array_get($params, 'medias'))) {
            GameProfileMedia::where('game_profile_id', $gameProfileExisted->id)->delete();
            $this->createGameProfileMedias($gameProfileExisted->id, array_get($params, 'medias'));
        }
        return $gameProfile;
    }

    public function createGameProfile($params, $isActive = Consts::TRUE)
    {
        $gameProfile = GameProfile::create([
            'user_id'       => Auth::id(),
            'game_id'       => array_get($params, 'game_id'),
            // 'rank_id'       => array_get($params, 'rank_id'),
            'title'         => array_get($params, 'title'),
            'audio'         => array_get($params, 'audio'),
            'is_active'     => $isActive
        ]);

        $this->createGameProfileOffers($gameProfile->id, array_get($params, 'offers'), $isActive);
        $this->createGamePlatforms($gameProfile->id, array_get($params, 'platform_ids'));

        // if (!empty(array_get($params, 'match_servers'))) {
        //     $this->createMatchServers($gameProfile->id, array_get($params, 'match_servers'));
        // }

        if (!empty(array_get($params, 'medias'))) {
            $this->createGameProfileMedias($gameProfile->id, array_get($params, 'medias'));
        }

        DB::table('game_profile_statistics')->insert(['game_profile_id' => $gameProfile->id]);

        if ($isActive) {
            $this->sendEmailAndNotify(Auth::id(), $gameProfile);
            event(new GameProfileUpdated($gameProfile->id));

            CalculateGameStatistic::dispatch($gameProfile->game_id, $gameProfile, Consts::GAME_STATISTIC_CREATE_GAME_PROFILE)->onQueue(Consts::QUEUE_CALCULATE_STATISTIC);
        }

        CollectTaskingJob::dispatch(Auth::id(), Tasking::CREATE_SESSION);

        return $gameProfile;
    }

    private function sendEmailAndNotify($userId, $gameProfile)
    {
        $userIdList = UserFollowing::where('following_id', $userId)
            ->where('is_following', Consts::TRUE)
            ->pluck('user_id');

        $user = User::select('id', 'username', 'sex', 'avatar')
            ->where('id', $userId)
            ->first();

        $game = Game::select('id', 'title', 'slug')
            ->where('id', $gameProfile->game_id)
            ->first();

        // send to owner
        Mail::queue(new GameProfileOnlineMail($gameProfile));
        $notificationParams = [
            'user_id' => $userId,
            'type' => Consts::NOTIFY_TYPE_SESSION_ONLINE,
            'message' => Consts::NOTIFY_YOUR_NEW_GAME_PROFILE,
            'props' => [],
            'data' => [
                'user' => (object) ['id' => $userId],
                'type' => Consts::OBJECT_TYPE_SESSION,
                'game_id' => $gameProfile->game_id
            ]
        ];
        $this->fireNotification(Consts::NOTIFY_TYPE_OTHER, $notificationParams);

        // send to followers
        foreach ($userIdList as $id) {
            $notificationUserParams = [
                'user_id' => $id,
                'type' => Consts::NOTIFY_TYPE_FAVORITE,
                'message' => Consts::NOTIFY_NEW_GAME_PROFILE,
                'props' => [],
                'data' => [
                    'user' => (object) ['id' => $userId],
                    'type' => Consts::OBJECT_TYPE_SESSION,
                    'game_id' => $gameProfile->game_id,
                    'mailable' => new NewGameProfileMail($id, $gameProfile)
                ]
            ];
            $this->fireNotification(Consts::NOTIFY_TYPE_FAVORITE, $notificationUserParams);
        }
    }

    public function updateGameProfile($gameProfileId, $params, $isActive = Consts::TRUE)
    {
        $gameProfile = GameProfile::findorfail($gameProfileId);
        $gameProfile = $this->saveData($gameProfile, $params);

        if (!empty(array_get($params, 'offers'))) {
            $this->updateGameProfileOffers($gameProfile->id, array_get($params, 'offers'), $isActive);
        }

        if (!empty(array_get($params, 'platform_ids'))) {
            $this->updateGamePlatforms($gameProfile->id, array_get($params, 'platform_ids'));
        }

        $this->updateGameProfileMedias($gameProfile->id, array_get($params, 'medias', []));

        // if (!empty(array_get($params, 'match_servers'))) {
        //     $this->updateMatchServers($gameProfile->id, array_get($params, 'match_servers'));
        // }

        if ($gameProfile->is_active) {
            event(new GameProfileUpdated($gameProfileId));
        }

        return $gameProfile;
    }

    public function deleteGameProfile($gameProfileId)
    {
        $gameProfile = GameProfile::findorfail($gameProfileId);
        $userId = $gameProfile->user_id;
        if ($gameProfile->canDeleteGameprofile()) {
            $gameProfile->delete();
            event(new GameProfileDeletedUpdated($gameProfileId, $userId));
            return true;
        }
        throw new GameProfileException('exceptions.game_profile.being_processed');
    }

    private function createGameProfileOffers($gameProfileId, $offers, $hasPrice = Consts::FALSE)
    {
        $isPremiumGamelancer = Auth::user()->user_type === Consts::USER_TYPE_PREMIUM_GAMELANCER;
        foreach ($offers as $offer) {
            if ($isPremiumGamelancer) {
                $this->validateRestrictPricing((object) $offer);
            }

            $data = array_merge($offer, ['game_profile_id' => $gameProfileId]);
            if (($hasPrice || $isPremiumGamelancer) && $data['price']) {
                GameProfileOffer::create($data);
            }
        }
    }

    private function validateRestrictPricing($offer)
    {
        $userId = Auth::id();
        $userRestrictPricing = UserRestrictPricing::where('user_id', $userId)->first();
        if (empty($userRestrictPricing)) {
            return;
        }

        $price = $offer->price;
        if (BigNumber::new($price)->sub($userRestrictPricing->min)->isNegative()) {
            throw ValidationException::withMessages([
                'price' => [__('validation.min.numeric', ['attribute' => 'price', 'min' => $userRestrictPricing->min])]
            ]);
        }

        if (BigNumber::new($userRestrictPricing->max)->sub($price)->isNegative()) {
            throw ValidationException::withMessages([
                'price' => [__('validation.max.numeric', ['attribute' => 'price', 'max' => $userRestrictPricing->max])]
            ]);
        }
    }

    private function updateGameProfileOffers($gameProfileId, $offers, $isActive = Consts::TRUE)
    {
        $existedOffers = GameProfileOffer::select('id', 'type', 'quantity', 'price')
            ->where('game_profile_id', $gameProfileId)
            ->get()
            ->keyBy('id')
            ->transform(function ($item) { unset($item->id); return $item; })
            ->toArray();

        if ($existedOffers) {
            // delete old items
            $deleteOffers = array_diff_with_serialize($existedOffers, $offers);
            $this->deleteGameProfileOffers($gameProfileId, array_keys($deleteOffers));
        }

        // create new items
        $newOffers = $existedOffers ? array_diff_with_serialize($offers, $existedOffers) : $offers;
        $this->createGameProfileOffers($gameProfileId, $newOffers, $isActive);
    }

    private function deleteGameProfileOffers($gameProfileId, $offers)
    {
        GameProfileOffer::where('game_profile_id', $gameProfileId)
          ->whereIn('id', $offers)->delete();
    }

    private function createGamePlatforms($gameProfileId, $gamePlatforms)
    {
        foreach ($gamePlatforms as $gamePlatform) {
            GameProfilePlatform::create([
                'game_profile_id'   => $gameProfileId,
                'platform_id'       => $gamePlatform
            ]);
        }
    }

    private function updateGamePlatforms($gameProfileId, $gamePlatforms)
    {
        $existedGamePlatforms = GameProfilePlatform::where('game_profile_id', $gameProfileId)->pluck('platform_id');
        $existedGamePlatforms = $existedGamePlatforms->toArray();

        if ($existedGamePlatforms) {
            // delete old items
            $deleteGamePlatforms = array_diff($existedGamePlatforms, $gamePlatforms);
            $this->deleteGamePlatforms($gameProfileId, $deleteGamePlatforms);
        }

        // create new items
        $newGamePlatforms = $existedGamePlatforms ? array_diff($gamePlatforms, $existedGamePlatforms) : $gamePlatforms;
        $this->createGamePlatforms($gameProfileId, $newGamePlatforms);
    }

    private function deleteGamePlatforms($gameProfileId, $gamePlatforms)
    {
        GameProfilePlatform::where('game_profile_id', $gameProfileId)
            ->whereIn('platform_id', $gamePlatforms)
            ->delete();
    }

    private function createMatchServers($gameProfileId, $matchServers)
    {
        foreach ($matchServers as $matchServer) {
            GameProfileMatchServer::create([
                'game_profile_id'   => $gameProfileId,
                'game_server_id'    => $matchServer
            ]);
        }
    }

    private function updateMatchServers($gameProfileId, $matchServers)
    {
        $existedMatchServers = GameProfileMatchServer::where('game_profile_id', $gameProfileId)->pluck('game_server_id');
        $existedMatchServers = $existedMatchServers->toArray();

        if ($existedMatchServers) {
            // delete old items
            $deleteMatchServers = array_diff($existedMatchServers, $matchServers);
            $this->deleteMatchServers($gameProfileId, $deleteMatchServers);
        }

        // create new items
        $newMatchServers = $existedMatchServers ? array_diff($matchServers, $existedMatchServers) : $matchServers;
        $this->createMatchServers($gameProfileId, $newMatchServers);
    }

    private function deleteMatchServers($gameProfileId, $matchServers)
    {
        GameProfileMatchServer::where('game_profile_id', $gameProfileId)
          ->whereIn('game_server_id', $matchServers)
          ->delete();
    }

    private function createGameProfileMedias($gameProfileId, $medias)
    {
        foreach ($medias as $media) {
          $data = array_merge($media, ['game_profile_id' => $gameProfileId]);
          GameProfileMedia::create($data);
        }
    }

    private function updateGameProfileMedias($gameProfileId, $medias)
    {
        $existedGameMedias = GameProfileMedia::select('type', 'url', 'id')
            ->where('game_profile_id', $gameProfileId)
            ->get()
            ->toArray();

        if ($existedGameMedias) {
            // delete old items
            $deleteGameMedias = array_diff_with_serialize($existedGameMedias, $medias);
            $deleteGameIds = collect($deleteGameMedias)->pluck('id');
            $this->deleteGameProfileMedias($gameProfileId, $deleteGameIds);
        }

        // create new items
        $newGameMedias = $existedGameMedias ? array_diff_with_serialize($medias, $existedGameMedias) : $medias;
        $this->createGameProfileMedias($gameProfileId, $newGameMedias);
    }

    private function deleteGameProfileMedias($gameProfileId, $medias)
    {
      GameProfileMedia::where('game_profile_id', $gameProfileId)
        ->whereIn('id', $medias)
        ->delete();
    }

    public function createGameProfileMedia($gameProfileId, $params)
    {
        $media = GameProfileMedia::create($params);
        event(new GameProfileUpdated($gameProfileId));

        return $media;
    }

    public function deleteGameProfileMedia($gameProfileId, $mediaId)
    {
        $delete = GameProfileMedia::where('id', $mediaId)->delete();
        event(new GameProfileUpdated($gameProfileId));

        return $delete;
    }

    public function getGameProfileReviews($params)
    {
        return SessionReview::with(['userReview'])
            ->where('game_profile_id', array_get($params, 'game_profile_id'))
            ->paginate(array_get($params, 'limit', Consts::DEFAULT_PER_PAGE));
    }

    public function getExistedGameProfile($userId)
    {
        return GameProfile::join('games', 'games.id', 'game_profiles.game_id')
            ->select('game_profiles.game_id', 'games.slug')
            ->where('game_profiles.user_id', $userId)
            ->where('game_profiles.is_active', Consts::TRUE)
            ->get();
    }

    public function getGameStatistics()
    {
        return GameStatisticUtils::getGameStatistics();
    }

    public function quickMatching($params)
    {
        $empty = ['data'  => null, 'total' => 0];

        $game = MasterdataService::getOneTable('games')
            ->where('is_active', Consts::TRUE)
            ->where('slug', array_get($params, 'slug', null))
            ->first();

        if (!$game) {
            return $empty;
        }

        $data = $this->tryGettingGameProfileUserOnline($game->id, $params);
        // if ($data->isEmpty()) {
        //     $data = $this->getGameProfileForMatching($game->id, $params);
        // }

        // get from cache.
        list($cacheKey, $gameProfileIds, $sid) = $this->getMatchingFromCache($params, $game);

        // ignore old candicate.
        $data = collect(array_shuffle($data))->filter(function ($item) use ($gameProfileIds) {
            return !in_array($item['id'], $gameProfileIds);
        });

        if ($data->isEmpty()) {
            return $empty;
        }

        $data = $this->ignoreChatHistoriesIfNeed($data);

        $gameProfileId = $data->first()['id'];

        $data = ['game_profile_id' => $gameProfileId];
        $info = $this->getGameProfilesBuilder($data)->get()->first();

        $gameProfileIds[] = $gameProfileId;

        $key = sprintf('%s-%s', $cacheKey, time());
        $sid = $sid ?? gamelancer_hash($key);

        Cache::put(
            $cacheKey,
            ['sid' => $sid, 'data' => $gameProfileIds],
            static::USER_MATCHING_TIME_LIVE
        );

        return [
            'data'  => $info,
            'sid'   => $sid,
            'total' => count($data)
        ];
    }

    private function tryGettingGameProfileUserOnline($gameId, $params = [])
    {
        $data = $this->getGameProfileForMatching(
            $gameId,
            array_merge($params, [
                'is_online' => Consts::TRUE
            ])
        );

        $data = $this->ignoreUserOffline($data);

        logger()->info('===user online: ', [$data]);

        return $data;
    }

    private function ignoreUserOffline($data)
    {
        $user = $this->getUserViaGuard() ? $this->getUserViaGuard() : null;
        if (!$user) {
            return $data;
        }

        $userIdsOnline = collect(UserOnlineUtils::getAllUserOnlines())->pluck('user_id')->toArray();
        return collect($data)->filter(function ($item) use ($userIdsOnline) {
            return in_array($item['user_id'], $userIdsOnline);
        });
    }

    private function ignoreChatHistoriesIfNeed($data)
    {
        $user = $this->getUserViaGuard() ? $this->getUserViaGuard() : null;
        if (!$user) {
            return $data;
        }

        $channels = ChatUtils::getChannelsForUser($user);
        $metUserIds = $channels->getCollection()->pluck('user.id')->toArray();

        // candicate is smaller or equal than chat histories.
        if (count($data) <= count($metUserIds)) {
            return $data;
        }

        return collect($data)->filter(function ($item) use ($metUserIds) {
            return !in_array($item['user_id'], $metUserIds);
        });
    }

    public function getGameProfileCollection ()
    {
        $cacheKey = 'quick-matching-collection';
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $limitCollection = 30;

        $users = User::where('status', Consts::USER_ACTIVE)
            ->where(function ($query) {
                $query->where('email_verified', Consts::TRUE)
                    ->orWhere('phone_verified', Consts::TRUE);
            })
            ->whereIn('user_type', [Consts::USER_TYPE_PREMIUM_GAMELANCER])
            ->take($limitCollection)
            ->get()
            ->map(function ($user) {
                return [
                    'id'            => $user->id,
                    'avatar'        => $user->avatar,
                    'username'      => $user->username,
                    'user_type'     => $user->user_type,
                    'is_vip'        => $user->is_vip,
                    'sex'           => $user->sex
                ];
            });

        Cache::put($cacheKey, $users, static::USER_MATCHING_COLLECTION_TIME_LIVE);

        return $users;
    }

    private function getGameProfileForMatching($gameId, $params)
    {
        // limitation matching...
        $limit  = 10000;
        $userId = $this->getUserViaGuard() ? $this->getUserViaGuard()->id : 0;

        return GameProfile::where('game_profiles.game_id', $gameId)
            ->where('game_profiles.is_active', Consts::TRUE)
            ->where('game_profiles.user_id', '<>', $userId)
            ->whereHas('user', function ($query) use ($params) {
                $query->where('status', Consts::USER_ACTIVE)
                    ->when(!empty($params['genders']), function ($query2) use ($params) {
                        $query2->whereIn('sex', Utils::standardNumber(array_get($params, 'genders')));
                    })
                    ->when(!empty($params['languages']), function ($query2) use ($params) {
                        $languages = array_get($params, 'languages', []);
                        $languages = collect($languages)
                            ->map(function ($item) {
                                return Utils::escapeLike($item);
                            })->toArray();
                        $query2->where(function ($query3) use ($languages) {
                            return BuilderUtils::multipleLike($query3, 'languages', $languages);
                        });
                    })
                    ->when(!empty($params['ages']), function ($query2) use ($params) {
                        $ages = array_get($params, 'ages', []);
                        $query2->where(function ($query3) use ($ages) {
                            $ageColumn = DB::raw("DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(dob, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(dob, '00-%m-%d'))");
                            return BuilderUtils::queryMultipleRanges($query3, $ageColumn, $ages);
                        });
                    })
                    ->when(!empty($params['is_online']), function ($query2) use ($params) {
                        $query2->whereHas('settings', function ($query3) use ($params) {
                            $query3->where('online', Consts::TRUE);
                        });
                    })
                    ->when(!empty($params['ratings']), function ($query2) use ($params) {
                        $ratings = array_get($params, 'ratings', []);
                        $query2->whereHas('statistic', function ($query3) use ($ratings) {
                            BuilderUtils::queryMultipleRanges($query3, 'rating', $ratings);
                        });
                    })
                    ->when(!empty($params['rankings']), function ($query2) use ($params) {
                        $rankings = array_get($params, 'rankings', []);
                        $query2->whereHas('userRanking', function ($query3) use ($rankings) {
                            $query3->whereIn('ranking_id', Utils::standardNumber($rankings));
                        });
                    });
            })
            ->when(!empty($params['prices']), function ($query) use ($params) {
                $prices = array_get($params, 'prices', []);
                $query->whereHas('gameOffers', function ($query2) use ($prices) {
                    BuilderUtils::queryMultipleRanges($query2, 'price', $prices);
                });
            })
            ->when(!empty($params['platforms']), function ($query) use ($params) {
                $query->whereHas('platforms', function ($query2) use ($params) {
                    $query2->whereIn('platform_id', Utils::standardNumber(array_get($params, 'platforms')));
                });
            })
            ->select('game_profiles.*')
            ->take($limit)
            ->get()
            ->toArray();
    }

    private function getMatchingFromCache($params, $game)
    {
        $userId     = $this->getUserViaGuard() ? $this->getUserViaGuard()->id : 0;
        $cacheKey   = "user-{$userId}-game-{$game->id}-matching";

        $empty      = [$cacheKey, [], null];

        if (empty($params['sid'])) {
            return $empty;
        }

        $cacheData  = Cache::get($cacheKey) ?? [];
        $sid        = array_get($cacheData, 'sid', null);
        $data       = array_get($cacheData, 'data', null);

        if ($sid === $params['sid']) {
            return [$cacheKey, $data, $sid];
        }

        return $empty;
    }

    private function getUserViaGuard()
    {
        return Auth::guard('api')->user();
    }
}
