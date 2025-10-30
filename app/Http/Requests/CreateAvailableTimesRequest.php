<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use DB;

class CreateAvailableTimesRequest extends FormRequest
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
        $allValues = implode(',', [Consts::TRUE,Consts::FALSE]);

        return array_merge($this->getTimeRules(), [
            'weekday'       => 'required|numeric|gte:0|lte:6',
            'all'           => "in:{$allValues}",
            'timeoffset'    =>  'required|numeric|between:-5940,5940' // GTM -99 -> GTM +99
        ]);
    }

    private function getTimeRules()
    {
        if (empty(request()->all)) {
            return [
                'from'      => 'required|numeric|gte:0|lte:1440',
                'to'        => 'required|numeric|gte:0|lte:1440'
            ];
        }

        return [
            'from'          => 'nullable|numeric|gte:0|lte:1440',
            'to'            => 'nullable|numeric|gte:0|lte:1440'
        ];
    }

}
