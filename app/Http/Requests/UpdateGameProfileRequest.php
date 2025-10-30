<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use Illuminate\Validation\Rule;
use DB;
use Log;
use Auth;

class UpdateGameProfileRequest extends FormRequest
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
            'id'                => 'required|belong_gamelancer',
            // 'rank_id'           => 'rank_exists:' . $this->getGameId($this->id),
            'title'             => 'required|string|min:1|max:190',
            'offers'            => $isPremiumGamelancer ? 'required|array|max:1' : '',
            'offers.*.type'     => $isPremiumGamelancer ? 'required|string|valid_offer_type:' . $this->getGameId($this->id) : '',
            'offers.*.quantity' => $isPremiumGamelancer ? 'required|numeric|gte:1' : '',
            'offers.*.price'    => $isPremiumGamelancer ? 'required|numeric|gte:0' : '',
            'audio'             => 'required|string',
            // 'match_servers'     => $this->match_servers ? 'array' : '',
            // 'match_servers.*'   => $this->match_servers ? 'server_exists:' . $this->getGameId($this->id) : '',
            'platform_ids'      => $this->platform_ids ? 'array' : '',
            'platform_ids.*'    => $this->platform_ids ? "platform_exists:{$this->game_id}" : '',
            'medias'            => $this->medias ? 'array' : '',
            'medias.*.type'     => $this->medias ? [Rule::in($mediaTypes), 'required', 'string'] : '',
            'medias.*.url'      => $this->medias ? 'required|url' : '',
        ];
    }

    private function getGameId($gameProfileId)
    {
        if ($this->game_id) {
            return $this->game_id;
        }
        return DB::table('game_profiles')->where('id', $gameProfileId)->value('game_id');
    }
}
