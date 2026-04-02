<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("quiz_questions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("quiz_id")->constrained("quizzes")->onDelete("cascade");
            $table->text("question");
            $table->json("options");
            $table->char("correct_answer", 1);
            $table->text("explanation")->nullable();
            $table->integer("order")->default(0);
            $table->enum("difficulty", ["easy", "medium", "hard"])->nullable();
            $table->timestamps();
            $table->index("quiz_id");
            $table->index(["quiz_id", "order"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("quiz_questions");
    }
};
