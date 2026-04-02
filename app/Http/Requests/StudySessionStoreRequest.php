<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudySessionStoreRequest extends FormRequest
{
    private const MIN_GENERATED_ITEMS = 15;
    private const MAX_GENERATED_ITEMS = 50;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'input_source_type' => 'required|in:text,pdf',
            'input_text' => 'required_if:input_source_type,text|nullable|string|min:50|max:50000',
            'pdf_file' => 'required_if:input_source_type,pdf|nullable|file|mimes:pdf|max:10240',
            'tags' => ['nullable', 'string', 'max:255', 'regex:/^[\pL\pN\s,\-._]*$/u'],
            'flashcard_difficulty' => 'nullable|in:easy,average,hard',
            'flashcard_count' => 'nullable|integer|min:'.self::MIN_GENERATED_ITEMS.'|max:'.self::MAX_GENERATED_ITEMS,
            'quiz_difficulty' => 'nullable|in:easy,average,hard',
            'quiz_count' => 'nullable|integer|min:'.self::MIN_GENERATED_ITEMS.'|max:'.self::MAX_GENERATED_ITEMS,
        ];
    }
}
