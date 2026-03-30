<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\TransferDTO;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use DomainException;

class WalletService
{
    public function transfer(TransferDTO $dto): void
    {
        // 1: Atomic Lock no Redis por 10 segundos
        // Isso impede que qualquer outra requisição com a mesma chave entre aqui
        $lock = Cache::lock("transfer_lock_{$dto->idempotencyKey}", 10);

        if (!$lock->get()) {
            throw new DomainException('Esta transação já está a ser processada por outro processo.');
        }



        // 2. A Caixa Forte (Transação de Banco de Dados)
        try {
            // Muralha 2: Verificação de existência no Banco (Persistência)
            if (Transaction::where('idempotency_key', $dto->idempotencyKey)->exists()) {
                throw new DomainException('Esta transferência já foi concluída anteriormente.');
            }

             DB::transaction(function () use ($dto) {
            
            // 3. Pessimistic Locking: Trancamos as linhas do remetente e destinatário
            // IMPORTANTE: Ordenamos por ID para evitar Deadlocks se duas pessoas 
            // tentarem transferir uma para a outra ao mesmo tempo.
            $wallets = Wallet::whereIn('user_id', [$dto->senderId, $dto->receiverId])
                ->lockForUpdate() // NENHUM outro processo pode ler ou alterar estas linhas agora
                ->orderBy('id')
                ->get()
                ->keyBy('user_id');

            $senderWallet = $wallets->get($dto->senderId);
            $receiverWallet = $wallets->get($dto->receiverId);

            if (! $senderWallet || ! $receiverWallet) {
                throw new DomainException('Carteira de origem ou destino não encontrada.');
            }

            // 4. Validação do Saldo (Feita DEPOIS do Lock, garantindo a verdade absoluta)
            if ($senderWallet->balance < $dto->amount) {
                throw new DomainException('Saldo insuficiente para realizar a transferência.');
            }

            // 5. Execução Matemática
            $senderBalanceBefore = $senderWallet->balance;
            $senderWallet->balance -= $dto->amount;
            $senderWallet->save();

            $receiverBalanceBefore = $receiverWallet->balance;
            $receiverWallet->balance += $dto->amount;
            $receiverWallet->save();

            // 6. Registro Histórico Absoluto (Audit Trail)
            $debitTransaction = Transaction::create([
                'wallet_id'         => $senderWallet->id,
                'counterparty_id'   => $receiverWallet->user_id,
                'amount'            => -$dto->amount,
                'type'              => 'debit',
                'payment_method_id' => $dto->paymentMethodId,
                'idempotency_key'   => $dto->idempotencyKey,
                'balance_before'    => $senderBalanceBefore,
                'balance_after'     => $senderWallet->balance,
            ]);

            Transaction::create([
                'wallet_id'         => $receiverWallet->id,
                'counterparty_id'   => $senderWallet->user_id,
                'amount'            => $dto->amount,
                'type'              => 'credit',
                'payment_method_id' => $dto->paymentMethodId,
                'idempotency_key'   => $dto->idempotencyKey . '_receipt',
                'balance_before'    => $receiverBalanceBefore,
                'balance_after'     => $receiverWallet->balance,
            ]);         

            // 7. Orquestração Assíncrona Segura (Redis)
            DB::afterCommit(function () use ($debitTransaction) {
                try {
                    \App\Jobs\ProcessTransferNotification::dispatch($debitTransaction->id);
                } catch (\Exception $e) {
                    // Se o Redis ou a rede falhar após o MySQL salvar, a transação financeira 
                    // está garantida. Logamos a falha da notificação sem destruir a transferência.
                    \Illuminate\Support\Facades\Log::error(
                        "Falha ao despachar notificação da transferência {$debitTransaction->id}", 
                        ['error' => $e->getMessage()]
                    );
                }
            });
        });

    
        } finally {
            // Libertar o cadeado obrigatoriamente, aconteça o que acontecer
            $lock->release();            
        }
       
    }
}