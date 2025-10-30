<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Utils\BigNumber;

class Tasking extends Model
{
    use SoftDeletes;

    const EXPLORE_PLATFORM          = 'explore-platform';
    const CREATE_SESSION            = 'create-session';
    const UPLOAD_VIDEO_INTRO        = 'upload-video-intro';
    const PLAY_FREE_SESSION         = 'play-free-session';

    const WRITE_POST                = 'write-post';
    const FOLLOW_USER               = 'follow-user';
    const UPLOAD_VIDEO_DAILY        = 'upload-video-daily';
    const COMPLETE_SESSION          = 'complete-session';

    protected $fillable = [
        'type',
        'title',
        'code',
        'description',
        'short_title',
        'short_description',
        'exp',
        'threshold_exp_in_day',
        'bonus_value',
        'bonus_currency',
        'url',
        'order'
    ];

    public function getUserTasks($collecting = [])
    {
        $result = BigNumber::new($this->threshold_exp_in_day)
            ->div($this->exp)
            ->toString();

        $maxQuantity = BigNumber::round($result, BigNumber::ROUND_MODE_CEIL, 0);

        $quantityCollected = count(
            array_get($collecting, $this->id, [])
        );

        return [
            'id'                => $this->id,
            'type'              => $this->type,
            'title'             => $this->title,
            'code'              => $this->code,
            'description'       => $this->description,
            'exp'               => $this->exp,
            'exp_up_to'         => $this->threshold_exp_in_day,
            'bonus_value'       => $this->bonus_value,
            'bonus_currency'    => $this->bonus_currency,
            'url'               => $this->url,
            'order'             => $this->order,
            'total_collected'   => $quantityCollected,
            'max_quantity'      => $maxQuantity,
            'completed'         => BigNumber::new($quantityCollected)->comp($maxQuantity) >= 0
        ];
    }

    public static function getTaskByCode($code)
    {
        return Tasking::where('code', static::INTRO_TASK)->first();
    }
}
