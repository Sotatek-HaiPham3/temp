<?php

namespace App\Http\Requests;

use App\Consts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use DB;

class CreateTaskRequest extends FormRequest
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
            'type'                  => ['required',Rule::in(Consts::TASKING_TYPE_INTRO, Consts::TASKING_TYPE_DAILY, Consts::TASKING_TYPE_DAILY_CHECKIN)],
            'order'                 => 'required|numeric|unique:taskings,order,NULL,id,deleted_at,NULL',
            'title'                 => 'required|unique:taskings,title,NULL,id,deleted_at,NULL|max:190',
            'description'           => $this->description ? 'string' : '',
            'exp'                   => 'required|numeric',
            'url'                   => $this->url ? 'image' : '',
            'bonus_value'           => ($this->bonus_currency || $this->bonus_value) ? 'required_with:bonus_currency|numeric' : '',
            'bonus_currency'        => ($this->bonus_currency || $this->bonus_value) ? ['required_with:bonus_value', Rule::in(Consts::CURRENCY_COIN, Consts::CURRENCY_EXP, Consts::CURRENCY_BAR)] : []
        ];
    }

}
