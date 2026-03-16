<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserWithWalletSeeder extends Seeder
{
    public function run(): void
    {
        // Criar Usuário João (O Remetente)
        $joao = User::firstOrCreate(
            ['email' => 'joao@teste.com'],
            ['name' => 'João Silva', 'password' => Hash::make('123456')]
        );

        // Criar ou atualizar a carteira do João com 500 reais
        Wallet::updateOrCreate(
            ['user_id' => $joao->id],
            ['balance' => 500.00, 'currency' => 'BRL']
        );

        // Criar Usuário Maria (A Destinatária)
        $maria = User::firstOrCreate(
            ['email' => 'maria@teste.com'],
            ['name' => 'Maria Souza', 'password' => Hash::make('123456')]
        );

        // Criar a carteira da Maria com 0 reais
        Wallet::updateOrCreate(
            ['user_id' => $maria->id],
            ['balance' => 0.00, 'currency' => 'BRL']
        );
    }
}