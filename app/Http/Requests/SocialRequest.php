<?php

namespace App\Http\Requests;

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
            'provider'   => 'required|provider_valid',
            'token'      => 'required',
            'username'   => 'required|unique_username|string|max:20|regex:/^([A-Za-z0-9]){3,}$/',
            'email'      => 'required|string|email|max:255|unique_email',
            // 'avatar'     => 'nullable|image|mimes:mp4,webm,jpg,jpeg,png,gif|max:2048',
            'agree_term' => 'required',
            'dob'        => 'required|before:-13 years|date_format:d/m/Y',
            'sex'        => 'required'
        ];
    }
}
