<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Consts;

class SessionSystemMessage extends Model
{
    protected $fillable = [
        'channel_id',
        'sender_id',
        'object_id',
        'object_type',
        'message_key',
        'message_props',
        'message_type',
        'data',
        'is_processed',
        'started_event'
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

    public static function getLatestSystemMessage($id, $type)
    {
        return SessionSystemMessage::where('object_type', $type)
            ->where('object_id', $id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
