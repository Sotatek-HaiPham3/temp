<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use App\Consts;
use App\Notifications\ResetPassword;
use App\Utils\BigNumber;
use App\Models\UserStripeCustomer;
use App\Models\ChangeEmailHistory;
use App\Models\UserConnectionHistory;
use App\Models\ChangeUsernameHistory;
use App\Models\ChangePhoneNumberHistory;
use App\Models\UserBalance;
use Carbon\Carbon;
use App\Http\Services\SystemNotification;
use App\Models\Traits\HasAttributesCustomTrait;
use App\Jobs\SendSmsNotificationJob;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasAttributesCustomTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'username',
        'full_name',
        'description',
        'user_type',
        'audio',
        'phone_number',
        'phone_country_code',
        'auto_accept_booking'
    ];

    // private $idols = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var
 array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $appends = [
        'existsCreditCard',
        'newEmail',
        'newUsername',
        'newPhoneNumber',
        'isFirstLogin',
        'isGamelancer'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];

    /**
     * Implements get user for Passport.
     *
     * @Reference Laravel\Passport\Bridge\UserRepository::getUserEntityByUserCredentials
     */
    public function findForPassport($username)
    {
        return $this->where(function ($query) use ($username) {
                $query->where('email', $username)
                    ->orWhere('username', $username)
                    ->orWhere('phone_number', $username);
            })
            ->first();
    }

    public function getIsGamelancerAttribute()
    {
        return $this->user_type === Consts::USER_TYPE_PREMIUM_GAMELANCER || $this->user_type === Consts::USER_TYPE_FREE_GAMELANCER;
    }

    public function getExistsCreditCardAttribute()
    {
        return UserStripeCustomer::where('user_id', $this->id)
                    ->whereNotNull('payment_method_id')
                    ->exists();
    }

    public function getNewEmailAttribute()
    {
        return ChangeEmailHistory::where('user_id', $this->id)->where('email_verified', Consts::FALSE)->value('new_email');
    }

    public function getNewUsernameAttribute()
    {
        return ChangeUsernameHistory::where('user_id', $this->id)->where('verified', Consts::FALSE)->value('new_username');
    }

    public function getNewPhoneNumberAttribute()
    {
        return ChangePhoneNumberHistory::where('user_id', $this->id)->where('verified', Consts::FALSE)->value('new_phone_number');
    }

    public function getIsFirstLoginAttribute()
    {
        return UserConnectionHistory::where('user_id', $this->id)->count() === 1;
    }

    public function setLanguagesAttribute($value)
    {
        $this->attributes['languages'] = implode(Consts::CHAR_COMMA, $value);
    }

    public function getLanguagesAttribute($value)
    {
        return explode(Consts::CHAR_COMMA, $value);
    }

    public function idols()
    {
        return $this->hasMany('App\Models\UserFollowing')
            ->select('user_id', 'following_id')
            ->where('is_following', Consts::TRUE);
    }

    public function fans()
    {
        return $this->hasMany('App\Models\UserFollowing', 'following_id', 'id')
            ->select('user_id', 'following_id')
            ->where('is_following', Consts::TRUE);
    }

    public function connectionHistories()
    {
        return $this->hasMany('App\Models\UserConnectionHistory');
    }

    public function socialNetworks()
    {
        return $this->hasMany('App\Models\UserSocialNetwork')
            ->where('visible', Consts::TRUE);
    }

    public function photos()
    {
        return $this->hasMany('App\Models\UserPhoto');
    }

    public function gameProfiles()
    {
        return $this->hasMany('App\Models\GameProfile');
    }

    public function bounties()
    {
        return $this->hasMany('App\Models\Bounty');
    }

    public function gamelancerInfo()
    {
        return $this->hasOne('App\Models\GamelancerInfo');
    }

    public function settings()
    {
        return $this->hasOne('App\Models\UserSetting', 'id', 'id')
            ->select('id', 'message_email', 'favourite_email', 'marketing_email', 'bounty_email', 'session_email', 'marketing_phone_number', 'bounty_phone_number', 'session_phone_number', 'public_chat', 'user_has_money_chat', 'auto_accept_booking');
    }

    public function visibleSettings()
    {
        return $this->hasOne('App\Models\UserSetting', 'id', 'id')
            ->select('id', 'visible_age', 'visible_gender', 'visible_following', 'online', 'cover');
    }

    public function userRanking()
    {
        return $this->hasOne('App\Models\UserRanking', 'user_id', 'id')
            ->with('ranking');
    }

    public function socialUser()
    {
        return $this->hasOne('App\Models\SocialUser');
    }

    public function availableTimes()
    {
        return $this->hasMany('App\Models\GamelancerAvailableTime')
            ->select('id', 'user_id', 'from', 'to');
    }

    public function personality()
    {
        return $this->hasMany('App\Models\ReviewTagStatistic')
            ->select('user_id', 'review_tag_id', 'quantity', 'review_type');
    }

    public function statistic()
    {
        return $this->hasOne('App\Models\UserStatistic');
    }

    public function emailChanging()
    {
        return $this->hasOne('App\Models\ChangeEmailHistory')
            ->select('user_id', 'new_email')
            ->where('email_verified', Consts::FALSE);
    }

    public function phoneChanging()
    {
        return $this->hasOne('App\Models\ChangePhoneNumberHistory')
            ->select('user_id', 'new_phone_number', 'new_phone_country_code')
            ->where('verified', Consts::FALSE);
    }

    public function phoneChangeCode()
    {
        return $this->hasOne('App\Models\ChangePhoneNumberHistory')
            ->select('user_id', 'verification_code')
            ->where('verified', Consts::FALSE);
    }

    public function setDobAttribute($value)
    {
        $this->attributes['dob'] = Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
    }

    public function changeEmailHistories()
    {
        return $this->hasMany('App\Models\ChangeEmailHistory');
    }

    public function isValid()
    {
        return $this->status === Consts::USER_ACTIVE;
    }

    public function sendPasswordResetNotification($token, $sendByPhoneNumber = Consts::FALSE, $timezoneOffset = null)
    {
        if ($sendByPhoneNumber) {
            SendSmsNotificationJob::dispatch($this, Consts::NOTIFY_SMS_PASSWORD_CODE, ['token' => $token]);
            return;
        }

        $this->notify(new ResetPassword(
            $token,
            $this->username,
            $this->email,
            $timezoneOffset
        ));
    }

    public function getDob()
    {
        return Carbon::parse($this->dob);
    }

    public function hasVerifiedEmail()
    {
        return $this->email_verified;
    }

    public function hasVerifiedPhone()
    {
        return $this->phone_verified;
    }

    public function isAccountVerified()
    {
        return $this->email_verified || $this->phone_verified;
    }

    public function canActiveAndCreateBalanceForUser()
    {
        return !UserBalance::where('id', $this->id)->exists();
    }

    public function isEmailVerificationCodeExpired()
    {
        return Carbon::parse($this->email_verification_code_created_at)->addDays(Consts::VERIFY_CODE_TIME_LIVE)->isPast();
    }

    public function isPhoneNumberVerificationCodeExpired()
    {
        return Carbon::parse($this->phone_verify_created_at)->addDays(Consts::VERIFY_CODE_TIME_LIVE)->isPast();
    }

    public function receivesBroadcastNotificationsOn()
    {
        return 'App.User.'.$this->id;
    }

    public function mattermostUser()
    {
        return $this->hasOne('App\Models\MattermostUser');
    }

    public function nodebbUser()
    {
        return $this->hasOne('App\Models\NodebbUser');
    }

    public function channelMembers()
    {
        return $this->hasMany('App\Models\ChannelMember');
    }

    /**
     * Get the address to send a notification to for change phone number.
     * NotificationChannels\Twilio\TwilioChannel::getTo
     */
    public function routeNotificationFor()
    {
        return $this->newPhoneNumber;
    }
}
