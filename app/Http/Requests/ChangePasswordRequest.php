<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        return [
            'old_password' => $user->password ? 'required|correct_password' : '',
            'password' => 'required|string|min:8|max:72|regex:/^(?=.*[a-z])(?=.*[A-Z]).+$/|password_white_space|different:old_password',
            'confirmation_password' => 'required|same:password'
        ];
    }

    public function messages()
    {
        return [
            'confirmation_password.same' => 'The retype password and new password must match.'
        ];
    }
}
