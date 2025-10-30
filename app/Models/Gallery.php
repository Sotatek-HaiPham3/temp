<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Gallery extends Model
{
    protected $table = 'gallery';

    use SoftDeletes;

    protected $fillable = [
        'wep_url',
        'app_url'
    ];
}
