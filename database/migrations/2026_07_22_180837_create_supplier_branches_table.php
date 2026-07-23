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
        Schema::create('supplier_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');

            $table->enum('type', [
                'fiscal',      // dirección fiscal / domicilio legal del RUC
                'laboratory',  // dirección de laboratorio
                'field',       // dirección de campo / operativa
                'warehouse',   // futuro: almacén del proveedor
                'other',
            ])->default('fiscal');

            $table->string('name');            // "Laboratorio Arequipa", "Planta Chala"
            $table->boolean('is_main')->default(false);

            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_branches');
    }
};
