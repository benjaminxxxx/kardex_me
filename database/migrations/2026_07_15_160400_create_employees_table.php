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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Persona asociada
            $table->foreignId('person_id')
                ->constrained('persons')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Código interno del trabajador (legible, no UUID)
            $table->string('employee_code', 30)->unique();

            // Fechas laborales generales (resumen, no reemplazan a employee_contracts)
            $table->date('hire_date');
            $table->date('termination_date')->nullable();

            // Estado del trabajador dentro del sistema
            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
                'terminated',
            ])->default('active');

            // Observaciones generales
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->auditColumns();
            $table->softDeletes();

            // Una persona solo puede tener un registro de empleado
            $table->unique('person_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
