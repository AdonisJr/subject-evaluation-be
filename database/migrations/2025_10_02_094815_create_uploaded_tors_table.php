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
        Schema::create('uploaded_tors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // who uploaded
            $table->string('file_path'); // storage path of uploaded TOR
            $table->string('public_id'); // storage path of uploaded TOR
            $table->enum('status', ['submitted', 'pending', 'analyzed', 'failed', 'processing','advising', 'done', 'rejected'])->default('submitted');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uploaded_tors');
    }
};
