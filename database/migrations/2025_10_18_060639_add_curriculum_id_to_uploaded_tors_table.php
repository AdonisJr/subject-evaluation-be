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
        Schema::table('uploaded_tors', function (Blueprint $table) {
            $table->foreignId('curriculum_id')->after('user_id')->constrained('curriculums')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('uploaded_tors', function (Blueprint $table) {
            $table->dropForeign(['curriculum_id']);
            $table->dropColumn('curriculum_id');
        });
    }
};
