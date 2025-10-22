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
            $table->foreignId('tor_id')->nullable()->constrained('uploaded_tors')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('credited_id')->nullable()->constrained('subjects')->onDelete('set null');
            $table->string('extracted_code')->nullable(); // Original code from OCR / TOR
            $table->string('credited_code')->nullable(); // Original code from OCR / TOR
            $table->string('title')->nullable(); // Subject name
            $table->float('grade')->nullable(); // Grade from TOR
            $table->decimal('credits', 5, 2)->default(0); // Units
            $table->tinyInteger('is_credited')->default(0);
            $table->float('percent_grade')->nullable();
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
