<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("flashcards", function (Blueprint $table) {
            $table->id();
            $table->foreignId("study_session_id")->constrained()->onDelete("cascade");
            $table->text("question");
            $table->text("answer");
            $table->integer("order")->default(0);
            $table->enum("difficulty", ["easy", "medium", "hard"])->nullable();
            $table->timestamps();
            $table->index("study_session_id");
            $table->index(["study_session_id", "order"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("flashcards");
    }
};
