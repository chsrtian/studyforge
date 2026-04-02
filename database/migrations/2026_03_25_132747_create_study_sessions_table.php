<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create("study_sessions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->string("title");
            $table->longText("input_text");
            $table->integer("input_word_count")->default(0);
            $table->enum("status", ["pending", "processing", "completed", "failed"])->default("completed");
            $table->json("metadata")->nullable();
            $table->timestamps();
            $table->index("user_id");
            $table->index("created_at");
            $table->index("status");
            $table->index(["user_id", "created_at"]);
        });
    }
    public function down(): void {
        Schema::dropIfExists("study_sessions");
    }
};
