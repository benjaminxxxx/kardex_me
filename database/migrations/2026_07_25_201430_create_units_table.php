<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('sunat_code', 10)->unique(); // NIU, KGM, MTR... (inmutable, tabla oficial SUNAT)
            $table->string('name');                      // "UNIDAD (BIENES)"
            $table->string('alias', 20)->nullable();     // "u", "kg", "m"
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
