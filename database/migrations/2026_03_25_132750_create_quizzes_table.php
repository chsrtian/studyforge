<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("quizzes", function (Blueprint $table) {
            $table->id();
            $table->foreignId("study_session_id")->constrained()->onDelete("cascade");
            $table->string("title");
            $table->text("description")->nullable();
            $table->integer("total_questions")->default(0);
            $table->integer("time_limit")->nullable();
            $table->timestamps();
            $table->index("study_session_id");
        });
    }
    public function down(): void {
        Schema::dropIfExists("quizzes");
    }
};
