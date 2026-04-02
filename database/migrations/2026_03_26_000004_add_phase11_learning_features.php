<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_sessions', function (Blueprint $table) {
            $table->boolean('is_bookmarked')->default(false)->after('status');
            $table->boolean('is_pinned')->default(false)->after('is_bookmarked');
            $table->unsignedInteger('review_count')->default(0)->after('is_pinned');
            $table->timestamp('last_reviewed_at')->nullable()->after('review_count');
            $table->timestamp('next_review_at')->nullable()->after('last_reviewed_at');

            $table->index(['user_id', 'is_pinned']);
            $table->index(['user_id', 'is_bookmarked']);
            $table->index(['user_id', 'next_review_at']);
        });

        Schema::create('study_streaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_study_date')->nullable();
            $table->timestamps();
        });

        Schema::create('study_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->unsignedInteger('weekly_session_target')->default(5);
            $table->timestamps();
        });

        Schema::create('session_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('study_session_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained()->onDelete('cascade');
            $table->foreignId('session_tag_id')->constrained('session_tags')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['study_session_id', 'session_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_session_tag');
        Schema::dropIfExists('session_tags');
        Schema::dropIfExists('study_goals');
        Schema::dropIfExists('study_streaks');

        Schema::table('study_sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'is_pinned']);
            $table->dropIndex(['user_id', 'is_bookmarked']);
            $table->dropIndex(['user_id', 'next_review_at']);
            $table->dropColumn([
                'is_bookmarked',
                'is_pinned',
                'review_count',
                'last_reviewed_at',
                'next_review_at',
            ]);
        });
    }
};
