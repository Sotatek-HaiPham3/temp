<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utils;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'transaction_id',
        'paypal_token',
        'offer_id',
        'real_amount',
        'real_currency',
        'amount',
        'currency',
        'payment_type', // paypal or stripe
        'type', // deposit or withdraw
        'status',
        'without_logged', // deposit without logged
        'memo',
        'message_key',
        'message_props',
        'internal_type', // session || bounty || tip
        'internal_type_id',
        'error_detail',
        'paypal_receiver_email', // email paypal for withdrawal
        'created_at',
        'updated_at'
    ];

    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();
        static::updating(function ($model) {
            $model->updated_at = Utils::currentMilliseconds();
        });
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
