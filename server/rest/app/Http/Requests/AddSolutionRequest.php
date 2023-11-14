<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AddSolutionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            "content" => "string|regex:https:\/\/drive\.google\.com\/file\/d\/([a-zA-Z0-9_-]{33})\/view\?usp=sharing|nullable",
            "assignment_uuid" => "uuid|required",
            "description" => "string|nullable"
        ];
        
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException($validator, $this->errorResponse($validator));
    }

    protected function errorResponse(\Illuminate\Contracts\Validation\Validator $validator)
    {
        return response()->json([
            'message' => 'Invalid Gdrive link',
            'errors' => $validator->errors(),
        ], 422);
    }
}
