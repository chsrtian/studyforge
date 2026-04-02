<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index(['chat_thread_id', 'created_at'], 'chat_messages_thread_created_idx');
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->index(['study_session_id', 'created_at'], 'chat_threads_session_created_idx');
        });

        Schema::table('session_input_sources', function (Blueprint $table) {
            $table->index(['study_session_id', 'extraction_status'], 'session_input_sources_session_status_idx');
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'created_at'], 'study_sessions_user_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_messages_thread_created_idx');
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropIndex('chat_threads_session_created_idx');
        });

        Schema::table('session_input_sources', function (Blueprint $table) {
            $table->dropIndex('session_input_sources_session_status_idx');
        });

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex('study_sessions_user_status_created_idx');
        });
    }
};
