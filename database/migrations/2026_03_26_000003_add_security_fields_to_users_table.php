<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->unsignedInteger('login_count')->default(0)->after('last_login_at');
            $table->unsignedInteger('current_streak')->default(0)->after('login_count');
            $table->unsignedInteger('longest_streak')->default(0)->after('current_streak');
            $table->date('last_study_date')->nullable()->after('longest_streak');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_login_at',
                'login_count',
                'current_streak',
                'longest_streak',
                'last_study_date',
            ]);
        });
    }
};
