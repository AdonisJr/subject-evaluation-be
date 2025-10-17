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
        Schema::create('tor_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tor_id')->constrained('uploaded_tors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->string('credited_code'); // Original code from OCR / TOR
            $table->string('title'); // Subject name
            $table->string('grade')->nullable(); // Grade from TOR
            $table->decimal('credits', 5, 2)->default(0); // Units
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tor_grades');
    }
};
