<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfilePicture extends FormRequest
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
            'image' => 'required|image|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'image.required' => "The image field is required.",
            'image.image' => "The image field must be an image.",
            'image.max' => "The image size must be less than 2 MB."
    ];
  }
}
