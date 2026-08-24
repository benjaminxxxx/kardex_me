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
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();

            // Datos de la empresa
            $table->string('company_name')->nullable();
            $table->string('ruc', 11)->nullable();
            $table->text('fiscal_address')->nullable();

            // Parámetros operativos: FK real, nunca el nombre del almacén
            $table->foreignId('mine_dispatch_warehouse_id')->nullable()
                ->constrained('warehouses')->nullOnDelete();

            $table->foreignId('reception_warehouse_id')->nullable()
                ->constrained('warehouses')->nullOnDelete();
                
            $table->foreignId('purchase_default_warehouse_id')->nullable()
                ->constrained('warehouses')->nullOnDelete();

            $table->boolean('restrict_distribution_to_requester')->default(true);
            
            $table->auditColumns();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
