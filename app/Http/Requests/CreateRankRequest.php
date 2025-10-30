<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use DB;

class CreateRankRequest extends FormRequest
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
            'name'                  => 'required|unique:rankings,name,NULL,id,deleted_at,NULL|max:190',
            'exp'                   => 'required|numeric',
            'url'                   => 'required|image',
            'threshold_exp_in_day'  => 'required|numeric'
        ];
    }

}
