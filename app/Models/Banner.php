<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'btn_caption',
        'thumbnail',
        'link',
        'is_active'
    ];
}
