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
        Schema::create('advisings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_tor_id')->constrained('uploaded_tors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('semester'); // first_sem or second_sem
            $table->string('subject_code');
            $table->string('year_level');
            $table->string('subject_title');
            $table->string('school_year')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advisings');
    }
};
