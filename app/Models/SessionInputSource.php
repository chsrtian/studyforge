<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionInputSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_session_id',
        'source_type',
        'original_filename',
        'file_path',
        'extracted_text',
        'extraction_status',
        'extraction_error',
        'file_size_bytes',
        'page_count',
    ];

    public function studySession()
    {
        return $this->belongsTo(StudySession::class);
    }
}
