<?php

namespace App\Providers;

use App\PhoneUtils;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use App\Http\Services\MasterdataService;
use App\Models\User;
use App\Models\Admin;
use App\Models\GameRank;
use App\Models\GameServer;
use App\Models\GameProfile;
use App\Models\GameType;
use App\Models\GamePlatform;
use App\Models\Bounty;
use App\Models\InvitationCode;
use App\Models\SessionReview;
use App\Models\RoomCategory;
use App\Consts;
use DB;
use Log;
use Auth;
use Propaganistas\LaravelPhone\PhoneNumber;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Validator::extend('unique_email', function($attribute, $value, $parameters, $validator) {
            $user = User::where('email', $value)->first();
            return !$user;
        });

        Validator::extend('unique_email_adminstrator', function($attribute, $value, $parameters, $validator) {
            return !Admin::where('email', $value)->exists();
        });

        Validator::extend('unique_username', function($attribute, $value, $parameters, $validator) {
            return !User::where('username', $value)->exists();
        });

        Validator::extend('unique_phone_number', function($attribute, $value, $parameters, $validator) {
            $phoneNumber = PhoneUtils::formatPhoneNumber($value);
            return !User::where('phone_number', $phoneNumber)
                ->when(!empty($parameters[0]), function ($query) use ($parameters) {
                    $query->where('id', '!=', $parameters[0]);
                })
                ->exists();
        });

        Validator::extend('exists_phone_number', function($attribute, $value, $parameters, $validator) {
            $phoneNumber = PhoneUtils::formatPhoneNumber($value);
            return User::where('phone_number', $phoneNumber)->exists();
        });

        Validator::extend('valid_phone_number', function($attribute, $value, $parameters, $validator) {
            if (PhoneUtils::getCountryCodeByFullPhoneNumber($value)) {
                return true;
            }
            return false;
        });

        Validator::extend('exists_username', function($attribute, $value, $parameters, $validator) {
            return User::where('username', $value)->exists();
        });

        Validator::extend('password_white_space', function($attribute, $value, $parameters, $validator) {
            return is_int(strpos($value, ' ')) ? false :true;
        });

        Validator::extend('verified_email', function($attribute, $value, $parameters, $validator) {
            $user = User::where('email', $value)->first();
            if ($user) {
                return $user->email_verified;
            }
            return true;
        });

        Validator::extend('verified_account', function($attribute, $value, $parameters, $validator) {
            $user = User::where('email', $value)
                ->orWhere('username', $value)
                ->orWhere('phone_number', $value)
                ->first();

            if ($user) {
                return $user->isAccountVerified();
            }

            return false;
        });

        Validator::extend('correct_password', function ($attribute, $value, $parameters, $validator) {
            $user = Auth::user();
            return (password_verify($value, $user->password));
        });

        Validator::extend('game_profile_exists', function ($attribute, $value, $parameters, $validator) {
            // update
            if ($parameters) {
                return !GameProfile::where('game_id', $parameters)
                    ->where('id', '<>', $value)
                    ->where('user_id', Auth::id())
                    ->where('is_active', Consts::TRUE)
                    ->exists();
            }

            // create new
            return !GameProfile::where('game_id', $value)
                ->where('user_id', Auth::id())
                ->where('is_active', Consts::TRUE)
                ->exists();
        });

        Validator::extend('rank_exists', function ($attribute, $value, $parameters, $validator) {
            return GameRank::where('game_id', $parameters)
                ->where('id', $value)
                ->exists();
        });

        Validator::extend('server_exists', function ($attribute, $value, $parameters, $validator) {
            return GameServer::where('game_id', $parameters)
                ->where('id', $value)
                ->exists();
        });

        Validator::extend('platform_exists', function ($attribute, $value, $parameters, $validator) {
            return GamePlatform::where('game_id', $parameters)
                ->where('platform_id', $value)
                ->exists();
        });

        Validator::extend('belong_gamelancer', function ($attribute, $value, $parameters, $validator) {
            return GameProfile::where('id', $value)
                ->where('user_id', Auth::id())
                ->exists();
        });

        Validator::extend('belong_user', function ($attribute, $value, $parameters, $validator) {
            return Bounty::where('id', $value)
                ->where('user_id', Auth::id())
                ->exists();
        });

        Validator::extend('valid_offer_type', function ($attribute, $value, $parameters, $validator) {
            $types = [Consts::GAME_TYPE_HOUR, Consts::GAME_TYPE_PER_GAME];
            return in_array($value, $types);
        });

        Validator::extend('is_offer_type', function ($attribute, $value, $parameters, $validator) {
            $types = GameType::where('game_id', $parameters)
                ->pluck('type');
            $types = $types ? $types->toArray() : [Consts::GAME_TYPE_HOUR];
            return in_array($value, $types);
        });

        Validator::extend('valid_invitation_code', function ($attribute, $value, $parameters, $validator) {
            return InvitationCode::where('code', $value)->exists();
        });

        Validator::extend('provider_valid', function ($attribute, $value, $parameters, $validator) {
            return in_array($value, Consts::SOCIAL_LIST);
        });

        Validator::extend('social_type_valid', function ($attribute, $value, $parameters, $validator) {
            $socialList = array_keys(Consts::SOCIAL_NETWORKS_LINK);
            return in_array($value, $socialList);
        });

        Validator::extend('special_characters', function ($attribute, $value, $parameters, $validator) {
            if (substr($value, 0, strlen(Consts::USER_DELETED_USERNAME_PREFIX)) === Consts::USER_DELETED_USERNAME_PREFIX) {
                return false;
            }
            return preg_match('/^[A-Za-z0-9_\.]+$/', $value);
        });

        Validator::extend('special_characters_email', function ($attribute, $value, $parameters, $validator) {
            if (substr($value, 0, strlen(Consts::USER_DELETED_USERNAME_PREFIX)) === Consts::USER_DELETED_USERNAME_PREFIX) {
                return false;
            }
            return true;
        });

        Validator::extend('valid_phone_country_code', function ($attribute, $value, $parameters, $validator) {
            return PhoneNumber::isValidCountryCode($value);
        });

        Validator::extend('valid_offer_price', function ($attribute, $value, $parameters, $validator) {
            $isGamelancer = Auth::user()->user_type === Consts::USER_TYPE_PREMIUM_GAMELANCER;
            if (!$isGamelancer) {
                return intval($value) === 0;
            }

            return intval($value) >= 0;
        });

        Validator::extend('valid_room_size_range', function ($attribute, $value, $parameters, $validator) {
            $value = explode($value, ',');
            $result = true;
            foreach ($value as $val) {
                if (!is_int(intval($val))) {
                    $result = false;
                    break;
                }
            }
            return $result || empty($value);
        });

        Validator::extend('room_category_existed', function ($attribute, $value, $parameters, $validator) {
            return !RoomCategory::where('game_id', $value)->exists();
        });

        View::composer('*', function ($view) {
            $dataVersion = MasterdataService::getDataVersion();
            $view->with('dataVersion', $dataVersion);
         });

        DB::enableQueryLog();
        DB::listen(function ($query) {
            $ignoreKyes = ['insert into `jobs`', 'select * from `jobs`'];
            foreach ($ignoreKyes as $key) {
                if (substr($query->sql, 0, strlen($key)) === $key) {
                    return;
                }
            }

            Log::debug('SQL', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'runtime' => $query->time
            ]);
        });
        DB::flushQueryLog();
    }
}
