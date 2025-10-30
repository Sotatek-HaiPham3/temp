<?php

namespace App\Http\Requests;

use App\Consts;
use Illuminate\Foundation\Http\FormRequest;

class SocialRequest extends FormRequest
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
            'provider'              => 'required|provider_valid',
            'token'                 => 'required',
            'username'              => 'required|unique:users,username|string|max:20|special_characters',
            'email'                 => 'nullable|string|email|max:255|unique:users,email',
            'phone_number'          => 'nullable|string|max:17|unique:users,phone_number',
            'dob'                   => 'required|before:-13 years|date_format:d/m/Y',
            'agree_term'            => 'required'
        ];
    }
}
