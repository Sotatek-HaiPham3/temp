<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStripeCustomerWithoutLogged extends Model
{
    protected $table = 'user_stripe_customers_without_logged';

    protected $fillable = ['user_id', 'customer_id', 'payment_method_id'];

    public static function getPaymentMethodId($userId)
    {
        $userStripeCustomer = static::where('user_id', $userId)
                                ->orderBy('created_at', 'desc')
                                ->first();

        return $userStripeCustomer->payment_method_id;
    }
}
