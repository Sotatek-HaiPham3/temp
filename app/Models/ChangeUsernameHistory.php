<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Consts;
use Carbon\Carbon;

class ChangeUsernameHistory extends Model
{
    use SoftDeletes;

    protected $table = 'change_username_histories';

    protected $fillable = [
        'user_id',
        'old_username',
        'new_username',
        'verification_code',
        'verified',
        'verification_code_created_at'
    ];

    public function isVerificationCodeExpired()
    {
        return Carbon::parse($this->verification_code_created_at)->addDays(Consts::VERIFY_CODE_TIME_LIVE)->isPast();
    }
}
