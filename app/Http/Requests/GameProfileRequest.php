<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Consts;
use DB;
use Log;
use Auth;

class GameProfileRequest extends FormRequest
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
        $isPremiumGamelancer = Auth::user()->user_type === Consts::USER_TYPE_PREMIUM_GAMELANCER;

        return [
            'game_id'           => 'required|exists:games,id|game_profile_exists',
            // 'rank_id'           => "required|rank_exists:{$this->game_id}",
            'title'             => 'string|min:1|max:190',
            'offers'            => $isPremiumGamelancer ? 'required|array|max:1' : '',
            'offers.*.type'     => $isPremiumGamelancer ? "required|string|valid_offer_type:{$this->game_id}" : '',
            'offers.*.quantity' => $isPremiumGamelancer ? 'required|numeric|gte:1' : '',
            'offers.*.price'    => $isPremiumGamelancer ? 'required|numeric|gte:0' : '',
            'audio'             => 'required|string',
            // 'match_servers'     => $this->match_servers ? 'array' : '',
            // 'match_servers.*'   => $this->match_servers ? "server_exists:{$this->game_id}" : '',
            'platform_ids'      => 'array',
            'platform_ids.*'    => "platform_exists:{$this->game_id}",
            'medias'            => $this->medias ? 'array' : '',
            'medias.*.type'     => $this->medias ? [Rule::in($mediaTypes), 'required', 'string'] : '',
            'medias.*.url'      => $this->medias ? 'required|url' : '',
        ];
    }

}
