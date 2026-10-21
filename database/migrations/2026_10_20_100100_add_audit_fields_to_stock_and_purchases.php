<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quién registró cada movimiento de stock (antes no se guardaba)
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('reason')->constrained('users')->nullOnDelete();
            $table->string('created_by_name')->nullable()->after('created_by');
            $table->foreignId('updated_by')->nullable()->after('created_by_name')->constrained('users')->nullOnDelete();
            $table->string('updated_by_name')->nullable()->after('updated_by');
        });

        // Motivo obligatorio al eliminar una compra
        Schema::table('purchases', function (Blueprint $table) {
            $table->text('deletion_reason')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['created_by_name', 'updated_by_name']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('deletion_reason');
        });
    }
};
