<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudySessionRegenerateSectionRequest extends FormRequest
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
            'section' => 'required|in:flashcards,quiz',
            'difficulty' => 'required|in:easy,average,hard',
            'count' => 'required|integer|min:'.self::MIN_GENERATED_ITEMS.'|max:'.self::MAX_GENERATED_ITEMS,
        ];
    }
}
