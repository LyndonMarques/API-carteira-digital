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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // decimal(15,2) suporta até 999 trilhões, ideal para a maioria das moedas
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();

            // Garantia de integridade: um usuário só pode ter uma carteira por moeda
            $table->unique(['user_id', 'currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
