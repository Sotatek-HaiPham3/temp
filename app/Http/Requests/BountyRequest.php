<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use DB;

class BountyRequest extends FormRequest
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
            'game_id'            => 'required|exists:games,id',
            'title'              => 'required|string|max:190',
            'description'        => 'required|string|max:1000',
            'price'              => 'required|numeric|gt:0',
            'media'              => $this->media ? 'url' : '',
            'platform_ids'       => 'array',
            'platform_ids.*'     => "platform_exists:{$this->game_id}",
            // 'server_ids'         => $this->server_ids ? 'array' : '',
            // 'server_ids.*'       => $this->server_ids ? "server_exists:{$this->game_id}" : '',
            // 'rank_id'            => 'required|exists:game_ranks,id',
            // 'user_level_meta_id' => 'required|exists:user_levels_meta,id'
        ];
    }
}
