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
        // purchase_items
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('presentation_id')->nullable()->constrained('product_presentations');

            $table->decimal('quantity', 12, 4);           // cantidad en la presentación elegida (ej. 10 cajas)
            $table->decimal('quantity_base', 12, 4);       // convertida a unidad base, snapshot histórico

            $table->decimal('unit_cost', 12, 4);           // costo por unidad de la presentación elegida
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('igv_percent', 5, 2)->default(18);
            $table->decimal('line_total', 12, 4)->default(0);
            $table->decimal('unit_cost_base', 18, 6)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
