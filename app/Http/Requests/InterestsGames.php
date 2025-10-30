<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Validator;

class InterestsGames extends FormRequest
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
            '*.game_id'             => 'required|exists:games,id',
            '*.platform_id'         => 'required|exists:platforms,id',
            '*.server_ids'          => 'required|array',
            '*.game_name'           => 'required|max:190',
        ];
    }

    /**
     * Validate the class instance.
     *
     * @return void
     */
    public function validateResolved()
    {
        parent::validateResolved();

        $this->validateServerIds();
    }

    private function validateServerIds()
    {
        $data = request()->all();

        $errors = [];

        foreach ($data as $key => $value) {
            $gameId = $value['game_id'];
            $serverIds = $value['server_ids'];

            $validator = Validator::make(
                [
                    'server_ids' => $serverIds
                ],
                [
                    'server_ids.*' => "server_exists:{$gameId}"
                ]
            );

            if ($validator->fails()) {
                $errors["{$key}.server_ids"] = __('validation.server_exists', [
                    'attribute' => __("validation.attributes.server_ids")
                ]);
            }
        }

        if (! empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
