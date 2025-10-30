<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Consts;
use Carbon\Carbon;

class ChangeEmailHistory extends Model
{
    use SoftDeletes;

    protected $table = 'change_email_histories';

    protected $fillable = [
        'user_id',
        'old_email',
        'new_email',
        'email_verification_code',
        'email_verified',
        'email_verification_code_created_at',
        'without_verified_account'
    ];

    public function isEmailVerificationCodeExpired()
    {
        return Carbon::parse($this->email_verification_code_created_at)->addDays(Consts::VERIFY_CODE_TIME_LIVE)->isPast();
    }
}
