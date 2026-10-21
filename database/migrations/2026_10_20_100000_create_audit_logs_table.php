<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de auditoría para acciones críticas (eliminaciones, cambios de
 * stock, accesos, configuración). Los registros no se editan ni se borran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Ej: purchase.deleted, kardex.deleted, stock_movement.deleted
            $table->string('event', 60)->index();
            // info | warning | critical
            $table->string('severity', 20)->default('info')->index();

            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->index(['auditable_type', 'auditable_id']);

            $table->string('description');
            $table->text('reason')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
