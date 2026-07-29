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
        Schema::create('kardex_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kardex_id')->constrained('kardexes')->cascadeOnDelete();

            // DOCUMENT REFERENCES
            $table->string('document_type', 10)->nullable(); // TABLE 10 (SUNAT)
            $table->string('document_series', 10)->nullable();
            $table->string('document_number', 20)->nullable();
            $table->unsignedSmallInteger('operation_type')->nullable(); // TABLE 12 (SUNAT)

            // Referencia al movimiento real, para trazabilidad — nunca se
            // recalcula automáticamente si el original cambia, solo permite auditar
            $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements');

            $table->date('movement_date');
            $table->enum('direction', ['in', 'out']);
            $table->string('source_label'); // snapshot del texto, ej "Despacho de explosivos"

            $table->decimal('entry_qty', 18, 4)->nullable();
            $table->decimal('entry_unit_cost', 18, 6)->nullable();
            $table->decimal('entry_total_cost', 18, 4)->nullable();

            $table->decimal('exit_qty', 18, 4)->nullable();
            $table->decimal('exit_unit_cost', 18, 6)->nullable();
            $table->decimal('exit_total_cost', 18, 4)->nullable();

            $table->decimal('balance_qty', 18, 4);
            $table->decimal('balance_unit_cost', 18, 6);
            $table->decimal('balance_total_cost', 18, 4);

            $table->timestamps();

            $table->index(['kardex_id', 'movement_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex_movements');
    }
};
