<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use App\DTOs\TransferDTO;

class WalletService
{
    /**
     * Processa uma transferência atômica P2P (Peer-to-Peer) com controle manual de transação.
     */
    public function transfer(TransferDTO $dto): bool
    {
        // 1. Filtro de Borda (Fora da transação para economizar recursos do banco)
        if ($dto->senderId === $dto->receiverId) {
            throw new \InvalidArgumentException('Operação inválida: não é possível transferir para si mesmo.');
        }

        // 2. Assume o controle manual da transação
        DB::beginTransaction();

        try {
            // PREVENÇÃO DE DEADLOCK: Ordena os user_ids
            $firstUserId = min($dto->senderId, $dto->receiverId);
            $secondUserId = max($dto->senderId, $dto->receiverId);

            // BUSCA E LOCK SIMULTÂNEO
            $firstWallet = Wallet::where('user_id', $firstUserId)->lockForUpdate()->firstOrFail();
            $secondWallet = Wallet::where('user_id', $secondUserId)->lockForUpdate()->firstOrFail();

            // MAPEAMENTO
            $senderWallet = $firstWallet->user_id === $dto->senderId ? $firstWallet : $secondWallet;
            $receiverWallet = $firstWallet->user_id === $dto->receiverId ? $firstWallet : $secondWallet;

            // VALIDAÇÃO DE DOMÍNIO
            if ($senderWallet->balance < $dto->amount) {
                // Usando DomainException em vez de Exception genérica
                throw new \DomainException('Saldo insuficiente para realizar a transferência.');
            }

            $senderBalanceBefore = $senderWallet->balance;
            $receiverBalanceBefore = $receiverWallet->balance;

            // MUTAÇÃO
            $senderWallet->balance -= $dto->amount;
            $receiverWallet->balance += $dto->amount;

            $senderWallet->save();
            $receiverWallet->save();

            // LEDGER (Livro Razão) - CAPTURANDO O OBJETO
            $debitTransaction = Transaction::create([
                'wallet_id' => $senderWallet->id,
                'amount' => $dto->amount,
                'type' => 'debit',
                'payment_method_id' => $dto->paymentMethodId,
                'idempotency_key' => $dto->idempotencyKey . '-out',
                'metadata' => ['receiver_id' => $receiverWallet->id],
                'balance_before' => $senderBalanceBefore,
                'balance_after' => $senderWallet->balance,
                'counterparty_id' => $receiverWallet->id,
            ]);

            Transaction::create([
                'wallet_id' => $receiverWallet->id,
                'amount' => $dto->amount,
                'type' => 'credit',
                'payment_method_id' => $dto->paymentMethodId,
                'idempotency_key' => $dto->idempotencyKey . '-in',
                'metadata' => ['sender_id' => $senderWallet->id],
                'balance_before' => $receiverBalanceBefore,
                'balance_after' => $receiverWallet->balance,
                'counterparty_id' => $senderWallet->id,
            ]);

            // 3. Efetivação no Banco (Ponto de não retorno)
            DB::commit();
            
            try {
                // Delega a carga pesada para o Redis isolando falhas de infraestrutura
                \App\Jobs\ProcessTransferNotification::dispatch($debitTransaction->id);
            } catch (\Exception $e) {
                // Se o Redis falhar, logamos o erro, mas NÃO quebramos a transferência do usuário
                Log::error("Falha ao despachar notificação da transferência {$debitTransaction->id}", ['error' => $e->getMessage()]);
            }

            // Respeita a assinatura public function transfer(): bool
            return true;

        } catch (QueryException $e) {
            // Desfaz tudo imediatamente
            DB::rollBack();
            
            // Intercepta violação de chave única (Idempotência falhou/Duplicidade)
            // Código 1062 é padrão do MySQL para Duplicate Entry
            if ($e->errorInfo[1] == 1062) {
                Log::warning("Tentativa de duplicidade na transferência", ['idempotency_key' => $dto->idempotencyKey]);
                throw new \DomainException('Esta transferência já foi processada.');
            }

            // Repassa erros de banco não previstos
            throw $e;

        } catch (\Exception $e) {
            // Desfaz qualquer alteração parcial em caso de erro de lógica ou saldo
            DB::rollBack();
            
            // Repassa a exceção para o Controller lidar
            throw $e;
        }
    }
}