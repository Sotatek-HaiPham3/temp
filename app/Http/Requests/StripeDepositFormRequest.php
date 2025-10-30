<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Auth;
use App\Consts;

class StripeDepositFormRequest extends FormRequest
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
        $notExistsCreditCard = !Auth::user()->existsCreditCard;
        return [
            'offer_id'              => 'required|exists:offers,id',
            'payment_method_id'     => $notExistsCreditCard ? 'required' : ''
        ];
    }
}
