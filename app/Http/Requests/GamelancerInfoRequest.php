<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Consts;

class GamelancerInfoRequest extends FormRequest
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
        $mediaTypes = [Consts::GAME_PROFILE_MEDIA_TYPE_IMAGE, Consts::GAME_PROFILE_MEDIA_TYPE_VIDEO];
        $values = [Consts::TRUE, Consts::FALSE];

        return [
            'total_hours'                   => 'required|string',
            'social_type'                   => 'required|social_type_valid',
            'social_id'                     => 'required',
            'introduction'                  => 'required|string',
            'only_bookable_online'          => [Rule::in($values), 'integer'],
            // 'invitation_code'               => $this->invitation_code ? 'string|valid_invitation_code' : '',
            'available_times'               => $this->available_times ? 'array' : '',
            'available_times.*.weekday'     => $this->available_times ? 'required|numeric|gte:0|lte:6' : '',
            'available_times.*.from'        => $this->available_times ? 'numeric|gte:0|lte:1440|nullable' : '',
            'available_times.*.to'          => $this->available_times ? 'numeric|gte:0|lte:1440|nullable' : '',
            'available_times.*.all'         => $this->available_times ? [Rule::in($values)] : '',
            'session.game_id'               => 'required|exists:games,id',
            // 'session.game_id'               => 'required|exists:games,id|game_profile_exists', // pending for downgrade become gamelancer
            // 'session.rank_id'               => "required|rank_exists:{$this->input('session')['game_id']}",
            'session.title'                 => 'required|string|max:190',
            'session.offers'                => 'required|array|max:1',
            'session.offers.*.type'         => "required|string|valid_offer_type:{$this->input('session')['game_id']}",
            'session.offers.*.quantity'     => 'required|numeric|gte:1',
            'session.offers.*.price'        => 'required|numeric|gte:0',
            // 'session.match_servers'         => $this->input('session')['match_servers'] ? 'array' : '',
            // 'session.match_servers.*'       => $this->input('session')['match_servers'] ? "server_exists:{$this->input('session')['game_id']}" : '',
            'session.platform_ids'          => 'array',
            'session.platform_ids.*'        => "platform_exists:{$this->input('session')['game_id']}",
            'session.medias'                => $this->input('session')['medias'] ? 'array' : '',
            'session.medias.*.type'         => $this->input('session')['medias'] ? [Rule::in($mediaTypes), 'required', 'string'] : '',
            'session.medias.*.url'          => $this->input('session')['medias'] ? 'required|url' : '',
            'timeoffset'                    =>  'required|numeric|between:-5940,5940' // GTM -99 -> GTM +99
        ];
    }
}
