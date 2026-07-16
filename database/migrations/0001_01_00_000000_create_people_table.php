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
        Schema::create('persons', function (Blueprint $table) {
            $table->id();

            // Internal code
            $table->string('code', 30)->unique();

            // Individual | Company
            $table->enum('type', ['individual', 'company'])
                ->default('individual');

            // Identity
            $table->string('document_type', 20);
            $table->string('document_number', 30);

            // Names
            $table->string('names')->nullable(); 
            $table->string('paternal_last_name')->nullable();
            $table->string('maternal_last_name')->nullable();

            // For companies
            $table->string('company_name')->nullable();

            // Cached names
            $table->string('display_name');
            $table->string('legal_name')->nullable();

            // Personal information
            $table->date('birth_date')->nullable();

            $table->enum('gender', [
                'male',
                'female',
                'other'
            ])->nullable();

            $table->enum('marital_status', [
                'single',
                'married',
                'divorced',
                'widowed'
            ])->nullable();

            // Contact
            $table->string('mobile', 30)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();

            // Address
            $table->string('country', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->text('address')->nullable();

            // Extra
            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique([
                'document_type',
                'document_number'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persons');
    }
};
