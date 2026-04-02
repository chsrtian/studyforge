<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    use HasFactory;

    protected $fillable = ['study_session_id', 'title'];

    public function studySession()
    {
        return $this->belongsTo(StudySession::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }
}
