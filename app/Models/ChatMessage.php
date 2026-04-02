<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['chat_thread_id', 'role', 'content', 'tokens_used'];

    public function thread()
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }
}
