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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('credited_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignId('tor_grade_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->foreignId('advising_id')->nullable()->constrained('advisings')->onDelete('set null');
            $table->enum('type', ['credited', 'advising']);
            $table->enum('status', ['done', 'enrolled', 'failed']);
            $table->integer('year_level')->nullable();
            $table->decimal('grade', 4, 2)->nullable();
            $table->integer('grade_percent')->nullable();
            $table->string('school_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
