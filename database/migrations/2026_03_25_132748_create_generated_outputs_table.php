<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("generated_outputs", function (Blueprint $table) {
            $table->id();
            $table->foreignId("study_session_id")->constrained()->onDelete("cascade");
            $table->enum("type", ["summary", "key_terms", "simplified_explanation"]);
            $table->json("content");
            $table->integer("generation_time")->nullable();
            $table->string("ai_model")->nullable();
            $table->integer("tokens_used")->nullable();
            $table->timestamps();
            $table->index("study_session_id");
            $table->index("type");
            $table->index(["study_session_id", "type"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("generated_outputs");
    }
};
