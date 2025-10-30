<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Consts;
use Carbon\Carbon;

class ChangePhoneNumberHistory extends Model
{
    use SoftDeletes;

    protected $table = 'change_phone_number_histories';

    protected $fillable = [
        'user_id',
        'old_phone_number',
        'new_phone_number',
        'new_phone_country_code',
        'verification_code',
        'verified',
        'verification_code_created_at',
        'without_verified_account'
    ];

    public function isVerificationCodeExpired()
    {
        return Carbon::parse($this->verification_code_created_at)->addDays(Consts::VERIFY_CODE_TIME_LIVE)->isPast();
    }
}
