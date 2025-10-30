<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStripeCustomer extends Model
{
    protected $table = 'user_stripe_customers';

    protected $fillable = ['user_id', 'customer_id', 'payment_method_id'];

    public static function getPaymentMethodId($userId)
    {
        $userStripeCustomer = static::where('user_id', $userId)->first();

        return $userStripeCustomer->payment_method_id;
    }
}
