<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'message_email',
        'favourite_email',
        'marketing_email',
        'bounty_email',
        'session_email',
        'marketing_phone_number',
        'bounty_phone_number',
        'session_phone_number',
        'public_chat',
        'user_has_money_chat',
        'auto_accept_booking',
        'only_online_booking',
        'visible_age',
        'visible_gender',
        'visible_following',
        'online',
        'cover',
        'follower_notification',
        'room_invite_notification',
        'room_start_notification'
    ];
}
