<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('session_input_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_session_id')->constrained('study_sessions')->cascadeOnDelete();
            $table->enum('source_type', ['text', 'pdf'])->default('text');
            $table->string('original_filename')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->longText('extracted_text')->nullable();
            $table->enum('extraction_status', ['pending', 'success', 'failed'])->default('pending');
            $table->text('extraction_error')->nullable();
            $table->integer('file_size_bytes')->nullable();
            $table->integer('page_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_input_sources');
    }
};
