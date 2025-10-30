<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Consts;

class StripeDepositWithoutLoggedFormRequest extends FormRequest
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
            'offer_id'              => 'required|exists:offers,id',
            'payment_method_id'     => 'required',
            'username'              => 'required|exists:users,username|verified_account'
        ];
    }
}
