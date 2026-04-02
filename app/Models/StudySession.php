<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudySession extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'input_text',
        'input_source_type',
        'extracted_text',
        'metadata',
        'status',
        'is_bookmarked',
        'is_pinned',
        'review_count',
        'last_reviewed_at',
        'next_review_at',
        'flashcards_regenerated_at',
        'quiz_regenerated_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_bookmarked' => 'boolean',
            'is_pinned' => 'boolean',
            'review_count' => 'integer',
            'last_reviewed_at' => 'datetime',
            'next_review_at' => 'datetime',
            'flashcards_regenerated_at' => 'datetime',
            'quiz_regenerated_at' => 'datetime',
        ];
    }

    public function inputSources(): HasMany
    {
        return $this->hasMany(SessionInputSource::class);
    }

    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function generatedOutputs(): HasMany
    {
        return $this->hasMany(GeneratedOutput::class);
    }

    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class);
    }

    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(SessionTag::class, 'study_session_tag');
    }
}
