<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeOffer extends Model
{
    protected $table = 'exchange_offers';

    protected $fillable = [
        'coins',
        'cover',
        'bars',
        'bonus',
    ];
}
