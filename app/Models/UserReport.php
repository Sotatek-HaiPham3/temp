<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserReport extends Model
{
    protected $table = 'user_reports';

    protected $fillable = [
        'user_id',
        'report_user_id',
        'reason'
    ];
}
