<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Consts;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use DB;

class UpdateTaskRequest extends FormRequest
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
            'order'                 => 'required|numeric|unique:taskings,order,'.$this->id.',id,type,'.$this->type.',deleted_at,NULL',
            'title'                 => 'required|unique:taskings,title,'.$this->id.',id,type,'.$this->type.',deleted_at,NULL|max:190',
            'description'           => $this->description ? 'string' : '',
            'short_title'           => $this->short_title ? 'string' : '',
            'short_description'     => $this->short_description ? 'string' : '',
            'exp'                   => 'required|numeric',
            'threshold_exp_in_day'  => $this->threshold_exp_in_day ? 'numeric' : '',
            'bonus_value'           => ($this->bonus_currency || $this->bonus_value) ? 'required_with:bonus_currency|numeric' : '',
            'bonus_currency'        => ($this->bonus_currency || $this->bonus_value) ? ['required_with:bonus_value', Rule::in(Consts::CURRENCY_COIN, Consts::CURRENCY_EXP, Consts::CURRENCY_BAR)] : []
        ];
    }

}
