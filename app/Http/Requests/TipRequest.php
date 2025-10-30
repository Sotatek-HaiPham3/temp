<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Auth;


class TipRequest extends FormRequest
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
        $userId = Auth::id();
        return [
            'receiver_id'   => [Rule::notIn([$userId]), 'required', 'exists:users,id'],
            'tip'           => 'required|numeric|gt:0',
            'type'          => $this->object_id ? 'required' : ''
        ];
    }
}
