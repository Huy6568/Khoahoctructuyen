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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('objectives')->nullable();
            $table->text('requirements')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('thumbnail_url')->nullable()->default("https://placehold.co/300x170?text=No+Image");
            $table->string('video_preview_url')->nullable();
            $table->enum('status', ['pending', 'draft', 'published', 'rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
