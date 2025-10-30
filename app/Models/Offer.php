<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Utils\BigNumber;

class Offer extends Model
{
    protected $fillable = [ 'coin', 'cover', 'stripe_cover', 'price', 'bonus', 'always_bonus' ];

    public function getAmountBonus()
    {
        return BigNumber::new($this->coin)->mul($this->bonus)->toString();
    }
}
