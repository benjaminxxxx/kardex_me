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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('persons');

            $table->string('supplier_code', 30)->unique(); // ej. PROV000001

            // Homologación (tu tabla supplier_approvals simplificada como estado, no como flujo aún)
            $table->enum('status', [
                'prospect',   // registrado, sin evaluar
                'approved',   // homologado, puede operar
                'suspended',
                'blacklisted',
            ])->default('prospect');

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
        Schema::dropIfExists('suppliers');
    }
};
