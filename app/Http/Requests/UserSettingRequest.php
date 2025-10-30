<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Consts;
use DB;
use Log;

class UserSettingRequest extends FormRequest
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
        $values = [Consts::TRUE, Consts::FALSE];

        return [
            'message_email'             => [Rule::in($values), 'integer'],
            'favourite_email'           => [Rule::in($values), 'integer'],
            'marketing_email'           => [Rule::in($values), 'integer'],
            'bounty_email'              => [Rule::in($values), 'integer'],
            'session_email'             => [Rule::in($values), 'integer'],
            'public_chat'               => [Rule::in($values), 'integer'],
            'user_has_money_chat'       => [Rule::in($values), 'integer'],
            'auto_accept_booking'       => [Rule::in($values), 'integer'],
            'visible_age'               => [Rule::in($values), 'integer'],
            'visible_gender'            => [Rule::in($values), 'integer'],
            'visible_following'         => [Rule::in($values), 'integer'],
            'online'                    => [Rule::in($values), 'integer'],
            'cover'                     => 'url'
        ];
    }
}
