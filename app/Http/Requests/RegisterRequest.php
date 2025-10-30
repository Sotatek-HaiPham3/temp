<?php

namespace App\Http\Requests;

use Auth;
use DB;
use Illuminate\Foundation\Http\FormRequest;
use Log;

class RegisterRequest extends FormRequest
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
            'username'           => 'required|unique_username|string|min:3|max:50|special_characters',
            'email'              => $this->phone_number ? '' : 'required|string|email|max:190|unique_email|regex:/^[\w+\.-]+@([\w-]+\.)+[\w-]{2,4}$/|special_characters_email',
            'password'           => $this->phone_number ? '' : 'required|string|min:8|max:20|regex:/^(?=.*[a-z]).+$/|confirmed|password_white_space',
            'phone_number'       => $this->email ? '' : 'required|string|max:17|unique_phone_number',
            'dob'                => 'required|before:-13 years|date_format:d/m/Y',
            'languages'          => 'required|array|min:1',
            'languages.*'        => 'required|string'
        ];
    }

}
