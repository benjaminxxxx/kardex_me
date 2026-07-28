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
        Schema::create('explosive_roles', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();   // detonator, charge, safety_fuse, guide, aux_guide
            $table->string('name');                  // "Fulminante", "Emulnor / Dinamita", "Mecha Lenta"...
            $table->unsignedInteger('sort_order')->default(0); // orden de columnas en el papel
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explosive_roles');
    }
};
