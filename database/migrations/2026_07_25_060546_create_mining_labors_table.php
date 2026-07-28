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
        Schema::create('mining_labors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // generado: TJ-138-RUBY

            $table->enum('labor_type', [
                'tajo',
                'subnivel',
                'galeria',
                'estocada',
                'crucero',
                'chimenea',
                'pique',
                'buzon',
            ]);

            // Apodo de veta — string simple, no tabla aparte (solo 34 valores hoy,
            // no justifica normalizar en catálogo separado todavía)
            $table->string('vein_name', 200); // RUBY, NELLY, LIDIA, KATY, CAROLINA, CERO...

            // Sube de 10 en 10 según me confirmaron, pero lo guardamos como número
            // libre, no como enum, porque la progresión es una convención de campo,
            // no una regla que la BD deba imponer
            $table->unsignedInteger('level_number');

            $table->enum('direction', ['este', 'oeste', 'norte', 'sur'])->nullable();

            $table->enum('status', ['active', 'exhausted', 'paused'])->default('active');

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->auditColumns();

            // Un mismo nivel+veta+tipo no debería repetirse como dos labores activas distintas
            $table->unique(['labor_type', 'level_number', 'vein_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mining_labors');
    }
};
