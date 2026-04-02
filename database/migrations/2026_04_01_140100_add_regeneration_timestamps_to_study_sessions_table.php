<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->timestamp('flashcards_regenerated_at')->nullable()->after('next_review_at');
            $table->timestamp('quiz_regenerated_at')->nullable()->after('flashcards_regenerated_at');
        });
    }

    public function down(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropColumn(['flashcards_regenerated_at', 'quiz_regenerated_at']);
        });
    }
};
