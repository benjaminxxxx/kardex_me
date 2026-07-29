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
        Schema::create('kardexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            // Snapshot del método vigente ESE año — nunca cambia aunque
            // luego alguien edite costing_year_settings retroactivamente
            $table->enum('costing_method', ['average', 'fifo']);

            $table->decimal('opening_qty', 18, 4)->default(0);
            $table->decimal('opening_unit_cost', 18, 6)->default(0);
            $table->decimal('opening_total_cost', 18, 4)->default(0);

            $table->decimal('total_entries_qty', 18, 4)->default(0);
            $table->decimal('total_entries_cost', 18, 4)->default(0);

            $table->decimal('total_exits_qty', 18, 4)->default(0);
            $table->decimal('total_exits_cost', 18, 4)->default(0);

            $table->decimal('closing_qty', 18, 4)->default(0);
            $table->decimal('closing_unit_cost', 18, 6)->default(0);
            $table->decimal('closing_total_cost', 18, 4)->default(0);

            $table->enum('status', ['open', 'closed'])->default('open');
            $table->string('excel_path')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->auditColumns();
            $table->timestamps();

            $table->unique(['product_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardexes');
    }
};
