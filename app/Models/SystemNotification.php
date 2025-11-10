<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SystemNotification extends Model
{
    use SoftDeletes;

    const TYPE_NEW_MESSAGE          = 'new_message';
    const TYPE_FAVORITE             = 'favorite';
    const TYPE_MARKETING            = 'marketing';
    const TYPE_BOUNTY               = 'bounty';
    const TYPE_SESSION              = 'session';
    const TYPE_VIDEO                = 'video';
    const TYPE_TASKING              = 'tasking';

    protected $fillable = [
        'type',
        'receiver_id',
        'message_key',
        'message_props',
        'data',
        'read_at',
        'view_at'
    ];

    public function setDataAttribute($value)
    {
        $this->attributes['data'] = json_encode($value);
    }

    public function getDataAttribute($value)
    {
        return json_decode($value);
    }

    public function setMessagePropsAttribute($value)
    {
        $this->attributes['message_props'] = json_encode($value);
    }

    public function getMessagePropsAttribute($value)
    {
        return json_decode($value);
    }
}
