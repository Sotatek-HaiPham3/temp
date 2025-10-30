<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use DB;

class UpdateBountyRequest extends FormRequest
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
        return [
            'id'                 => 'required|belong_user',
            'title'              => 'string|min:1|max:190',
            'description'        => 'string|min:1',
            'slug'               => $this->slug ? "unique:bounties,slug,{$this->id}" : '',
            'price'              => 'numeric|gte:10',
            'media'              => 'url',
            // 'match_servers'      => $this->match_servers ? 'array' : '',
            // 'match_servers.*'    => $this->match_servers ? "server_exists:{$this->getGameId($this->id)}" : '',
            'platform_ids'       => $this->platform_ids ? 'array' : '',
            'platform_ids.*'     => $this->platform_ids ? "platform_exists:{$this->game_id}" : '',
            // 'rank_id'            => 'exists:game_ranks,id',
            // 'user_level_meta_id' => 'exists:user_levels_meta,id'
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
