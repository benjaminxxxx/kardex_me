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
        Schema::create('explosive_buffer_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');

            $table->enum('movement_type', ['credit', 'debit']);
            // credit = sobrante que queda disponible (se generó al cerrar una distribución)
            // debit  = sobrante que se consumió como complemento en un nuevo despacho

            $table->decimal('quantity', 12, 4);
            $table->nullableMorphs('source'); // referencia al dispatch_item o a la distribución que lo originó
            $table->date('movement_date');

            $table->auditColumns();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explosive_buffer_movements');
    }
};
