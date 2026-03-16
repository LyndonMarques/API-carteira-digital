<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;


class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            ['id' => 1, 'name' => 'Transferência Interna'],
            ['id' => 2, 'name' => 'Pix'],
            ['id' => 3, 'name' => 'Cartão de Crédito'],
            ['id' => 4, 'name' => 'Boleto Bancário'],
        ];

        foreach ($methods as $method) {

            PaymentMethod::firstOrCreate(
                ['id' => $method['id']],
                ['name' => $method['name']]
            );
        }
    }
}
