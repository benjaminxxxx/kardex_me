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
        Schema::create('explosive_field_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispatch_id')->constrained('explosive_field_dispatches');
            $table->foreignId('mining_labor_id')->constrained('mining_labors');
            $table->foreignId('driller_employee_id')->constrained('employees');

            $table->unsignedInteger('drill_depth_feet')->nullable(); // LONG BARRENO PIES, necesario para calcular mecha/guía
            $table->unsignedInteger('guide_length_feet')->nullable()->default(5);
            $table->decimal('fulminante_qty', 12, 4)->default(0);     // ingresado manualmente
            $table->decimal('emulnor_qty', 12, 4)->default(0);        // ingresado manualmente
            $table->decimal('mecha_lenta_qty', 12, 4)->default(0);    // CALCULADO o ingresado manualmente
            $table->decimal('guia_qty', 12, 4)->default(0);           // CALCULADO o ingresado manualmente
            $table->decimal('guia_aux_qty', 12, 4)->default(0);       // ingresado manualmente
            $table->decimal('anfo_qty', 12, 4)->default(0);           // ingresado manualmente

            $table->auditColumns();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('explosive_field_distributions');
    }
};
