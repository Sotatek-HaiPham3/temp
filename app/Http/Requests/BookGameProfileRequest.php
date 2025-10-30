<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use DB;
use Log;

class BookGameProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $pattern = Consts::SESSION_SCHEDULE_AT_DATETIME_FORMAT;

        return [
            'game_profile_id'   => 'required|exists:game_profiles,id',
            'type'              => 'required|valid_offer_type:' . $this->getGameId(),
            'quantity'          => 'required|numeric|gte:0.5',
            'schedule'          => "date|date_format:{$pattern}|after:now|nullable",
            'timeoffset'        => 'required|numeric|between:-5940,5940' // GTM -99 -> GTM +99
        ];
    }

    private function getGameId()
    {
        return DB::table('game_profiles')->where('id', $this->game_profile_id)->value('game_id');
    }

}
