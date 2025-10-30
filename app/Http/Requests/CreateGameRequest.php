<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use App\Consts;
use DB;
use Log;

class CreateGameRequest extends FormRequest
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
            'title'             => 'required|unique:games|max:190',
            'slug'              => 'required|unique:games|max:190',
            'logo'              => 'required',
            'thumbnail'         => 'required',
            'portrait'          => 'required'
        ];
    }

}
