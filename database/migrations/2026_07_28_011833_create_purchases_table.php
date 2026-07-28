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
        // purchases
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('warehouse_id')->constrained('warehouses');

            $table->enum('currency', ['PEN', 'USD'])->default('PEN');
            $table->decimal('exchange_rate', 8, 4)->default(1);

            $table->enum('document_type', ['boleta', 'factura', 'nota_venta'])->default('factura');
            $table->string('document_number', 30)->nullable();
            $table->date('document_date');
            $table->date('due_date')->nullable();

            $table->enum('payment_method', ['contado', 'credito'])->default('contado');

            $table->decimal('subtotal_neto', 12, 4)->default(0);
            $table->decimal('igv_total', 12, 4)->default(0);
            $table->decimal('total', 12, 4)->default(0);

            $table->text('notes')->nullable();

            $table->auditColumns();
            $table->timestamps();
            $table->softDeletes();
        });

        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
