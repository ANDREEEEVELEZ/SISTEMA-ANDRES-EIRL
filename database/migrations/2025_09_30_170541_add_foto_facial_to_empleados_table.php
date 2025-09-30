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
        Schema::table('empleados', function (Blueprint $table) {
            $table->string('foto_facial_path')->nullable()->after('telefono');
            $table->text('face_descriptors')->nullable()->after('foto_facial_path')->comment('Descriptores faciales para reconocimiento');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['foto_facial_path', 'face_descriptors']);
        });
    }
};
