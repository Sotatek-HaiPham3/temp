<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClaimBountyActionRequest extends FormRequest
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
            'bounty_claim_request_id' => 'required|exists:bounty_claim_requests,id'
        ];
    }
}
