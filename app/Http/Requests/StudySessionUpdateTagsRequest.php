<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudySessionUpdateTagsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tags' => ['nullable', 'string', 'max:255', 'regex:/^[\pL\pN\s,\-._]*$/u'],
        ];
    }
}
