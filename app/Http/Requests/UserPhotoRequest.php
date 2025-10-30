<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Consts;
use DB;
use Log;

class UserPhotoRequest extends FormRequest
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
        $mediaTypes = [Consts::USER_MEDIA_PHOTO, Consts::USER_MEDIA_VIDEO];

        return [
            'type'      => [Rule::in($mediaTypes), 'required', 'string'],
            'url'       => 'required|url'
        ];
    }

}
