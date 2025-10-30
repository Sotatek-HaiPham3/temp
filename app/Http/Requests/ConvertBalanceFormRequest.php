<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConvertBalanceFormRequest extends FormRequest
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
            'exchange_offer_id' => 'required|exists:exchange_offers,id',
        ];
    }
}
