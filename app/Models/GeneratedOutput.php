<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedOutput extends Model
{
    protected $fillable = [
        'study_session_id',
        'type',
        'content',
    ];

    protected $casts = [
        'content' => 'array',
    ];

    public function studySession(): BelongsTo
    {
        return $this->belongsTo(StudySession::class);
    }
}
