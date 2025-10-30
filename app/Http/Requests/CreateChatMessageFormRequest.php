<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateChatMessageFormRequest extends FormRequest
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
            'channel_id' => 'required|exists:channels,mattermost_channel_id',
            'images'     => empty($this->message) ? 'required|array|max:1' : '',
            'message'    => empty($this->images) ? 'required|string|max:2000' : '',
            'temp_id'    => 'required'
        ];
    }
}
