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
        Schema::create('explosive_field_dispatches', function (Blueprint $table) {
            $table->id();
            $table->date('dispatch_date');
            $table->enum('shift', ['day', 'night']);
            $table->foreignId('dispatched_by_employee_id')->constrained('employees');
            $table->foreignId('requested_by_employee_id')->constrained('employees');

            $table->decimal('fulminante_qty', 12, 4)->default(0);
            $table->decimal('emulnor_qty', 12, 4)->default(0);       // dinamita
            $table->decimal('mecha_lenta_qty', 12, 4)->default(0);
            $table->decimal('guia_qty', 12, 4)->default(0);
            $table->decimal('guia_aux_qty', 12, 4)->default(0);
            $table->decimal('anfo_qty', 12, 4)->default(0);

            $table->foreignId('warehouse_id')->nullable()
                ->constrained('warehouses');

            $table->foreignId('fulminante_product_id')->nullable()
                ->constrained('products');
            $table->foreignId('emulnor_product_id')->nullable()
                ->constrained('products');
            $table->foreignId('mecha_lenta_product_id')->nullable()
                ->constrained('products');
            $table->foreignId('guia_product_id')->nullable()
                ->constrained('products');
            $table->foreignId('guia_aux_product_id')->nullable()
                ->constrained('products');
            $table->foreignId('anfo_product_id')->nullable()
                ->constrained('products');

            $table->enum('status', ['pending_distribution', 'distributed'])->default('pending_distribution');
            $table->text('notes')->nullable();
            $table->auditColumns();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explosive_field_dispatches');
    }
};
