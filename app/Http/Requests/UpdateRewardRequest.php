<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use DB;
use App\Consts;

class UpdateRewardRequest extends FormRequest
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
            'type'     => 'required',
            'level'    => 'required|numeric|unique:tasking_rewards,level,'.$this->id.',id,type,'.$this->type,
            'quantity' => 'required|numeric',
            'currency' => ['required', Rule::in(Consts::CURRENCY_COIN, Consts::CURRENCY_EXP, Consts::CURRENCY_BAR)]
        ];
    }

}
