<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                        
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('wallet_id')->index()->constrained(); #Referência à carteira que originou ou recebeu a transação    
            $table->decimal('amount', 15, 2); #O valor da transação            
            $table->enum('type', ['credit', 'debit']); #Crédito para entrada de fundos, Débito para saída de fundos
            $table->foreignId('payment_method_id')->constrained(); #Identificação de origem ou destino externo (ex: "Pix", "Boleto", "Transferência")        
            $table->string('idempotency_key')->unique(); #Chave de idempotência para evitar processamento duplicado            
            $table->json('metadata')->nullable(); #Quais campos seriam cruciais para um relatório de auditoria?

            $table->decimal('balance_after', 15, 2); #Saldo da carteira após a transação
            $table->decimal('balance_before', 15, 2); #Saldo da carteira antes da transação

            $table->unsignedBigInteger('counterparty_id')->nullable(); #Referência a outra carteira ou entidade envolvida na transação
    
            $table->foreign('counterparty_id')->references('id')->on('wallets')->nullOnDelete(); #Relacionamento opcional com outra carteira (ex: para transferências internas)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('transactions');
    }
};
