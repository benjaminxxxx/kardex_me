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
        Schema::create('supplier_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers');

            $table->enum('type', [
                'bank_account',  // cuenta bancaria tradicional
                'digital_wallet', // Yape, Bim, Plin
            ])->default('bank_account');

            // Solo aplica a bank_account
            $table->string('bank_name', 100)->nullable();     // BCP, BBVA, Interbank, Scotiabank
            $table->string('account_number', 30)->nullable();
            $table->string('cci', 30)->nullable();             // código interbancario, 20 dígitos
            $table->enum('currency', ['PEN', 'USD'])->default('PEN');

            // Solo aplica a digital_wallet
            $table->string('wallet_provider', 50)->nullable(); // Yape, Bim, Plin
            $table->string('wallet_phone', 30)->nullable();    // número vinculado

            // Común a ambos
            $table->string('account_holder_name')->nullable(); // a veces el titular no es el RUC exacto
            $table->boolean('is_main')->default(false);         // cuenta principal para pagos
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_bank_accounts');
    }
};
