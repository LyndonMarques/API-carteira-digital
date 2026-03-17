<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\WalletService;
use App\DTOs\TransferDTO;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;

class WalletServiceTest extends TestCase
{
    // Esta trait destrói e recria o banco de teste a cada execução. Essencial para isolamento.
    use RefreshDatabase;

    protected WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->walletService = app(WalletService::class);

        // Cria a dependência fixa que o WalletService exige em hardcode (ID 1)
        // Usamos o DB::table diretamente para evitar depender de Factories que talvez não existam para essa tabela auxiliar.
        \Illuminate\Support\Facades\DB::table('payment_methods')->insert([
            'id' => 1,
            'name' => 'Transferência Interna',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_deve_processar_transferencia_com_sucesso_e_gravar_ledger()
    {
        // 1. PREPARAÇÃO (Arrange)
        // Cria 2 usuários e 2 carteiras com saldo inicial de 100
        $sender = clone User::factory()->create();
        $receiver = clone User::factory()->create();

        Wallet::factory()->create(['user_id' => $sender->id, 'balance' => 100.00]);
        Wallet::factory()->create(['user_id' => $receiver->id, 'balance' => 100.00]);

        $dto = new TransferDTO(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 40.00,
            idempotencyKey: 'teste_sucesso_001',
            paymentMethodId: 1
        );

        // 2. AÇÃO (Act)
        $result = $this->walletService->transfer($dto);

        // 3. VALIDAÇÃO (Assert)
        $this->assertTrue($result);

        // Prova que o saldo do remetente caiu para 60
        $this->assertDatabaseHas('wallets', [
            'user_id' => $sender->id,
            'balance' => 60.00,
        ]);

        // Prova que o saldo do destinatário subiu para 140
        $this->assertDatabaseHas('wallets', [
            'user_id' => $receiver->id,
            'balance' => 140.00,
        ]);

        // Prova que o Livro Razão registrou as duas pontas da transação
        $this->assertEquals(2, Transaction::count());
        $this->assertDatabaseHas('transactions', [
            'amount' => 40.00,
            'type' => 'debit',
            'idempotency_key' => 'teste_sucesso_001-out'
        ]);
    }

    public function test_deve_bloquear_transferencia_por_saldo_insuficiente()
    {
        $sender = User::factory()->create();
        $receiver = User::factory()->create();

        // Remetente tem apenas 10
        Wallet::factory()->create(['user_id' => $sender->id, 'balance' => 10.00]);
        Wallet::factory()->create(['user_id' => $receiver->id, 'balance' => 100.00]);

        $dto = new TransferDTO(
            senderId: $sender->id,
            receiverId: $receiver->id,
            amount: 50.00, // Tenta enviar 50
            idempotencyKey: 'teste_saldo_001',
            paymentMethodId: 1
        );

        // Espera que o Service lance exatamente esta exceção de domínio
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $this->walletService->transfer($dto);

        // Como a exceção é lançada, o código abaixo desta linha nunca roda.
        // O banco (por causa do try/catch no service) deve ter sofrido rollback automático.
    }

    public function test_deve_rejeitar_transferencia_para_si_mesmo()
    {
        $sender = User::factory()->create();
        Wallet::factory()->create(['user_id' => $sender->id, 'balance' => 100.00]);

        $dto = new TransferDTO(
            senderId: $sender->id,
            receiverId: $sender->id, // ID igual
            amount: 10.00,
            idempotencyKey: 'teste_igual_001',
            paymentMethodId: 1
        );

        $this->expectException(\InvalidArgumentException::class);
        
        $this->walletService->transfer($dto);
    }
}