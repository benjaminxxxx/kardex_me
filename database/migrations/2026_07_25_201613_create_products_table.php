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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();          // FULM-001
            $table->string('name');
            $table->string('chemical_name')->nullable();    // "Emulnor"
            $table->string('brand')->nullable();

            $table->foreignId('category_id')->constrained('product_categories');
            $table->foreignId('unit_id')->constrained('units'); // unidad MÍNIMA (base), casi siempre NIU/KGM/MTR
            $table->foreignId('explosive_role_id')->nullable()->constrained('explosive_roles');
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('sunat_product_code', 20)->nullable()->index();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->softDeletes();
            $table->auditColumns();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
