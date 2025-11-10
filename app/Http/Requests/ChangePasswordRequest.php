<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
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
            'old_password' => 'required|correct_password',
            'new_password' => 'required|string|min:8|max:72|regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/|password_white_space|different:old_password',
            'repeat_password' => 'required|same:new_password'
        ];
    }
}
