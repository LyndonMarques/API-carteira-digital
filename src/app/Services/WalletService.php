<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class WalletService
{
    /**
     * Processa uma transferência atômica P2P (Peer-to-Peer) com controle manual de transação.
     */
    public function transfer(int $senderId, int $receiverId, string $amount, string $idempotencyKey): bool
    {
        // 1. Filtro de Borda (Fora da transação para economizar recursos do banco)
        if ($senderId === $receiverId) {
            throw new \InvalidArgumentException('Operação inválida: não é possível transferir para si mesmo.');
        }

        // 2. Assume o controle manual da transação
        DB::beginTransaction();

        try {
            // PREVENÇÃO DE DEADLOCK: Ordena os user_ids
            $firstUserId = min($senderId, $receiverId);
            $secondUserId = max($senderId, $receiverId);

            // BUSCA E LOCK SIMULTÂNEO
            $firstWallet = Wallet::where('user_id', $firstUserId)->lockForUpdate()->firstOrFail();
            $secondWallet = Wallet::where('user_id', $secondUserId)->lockForUpdate()->firstOrFail();

            // MAPEAMENTO
            $senderWallet = $firstWallet->user_id === $senderId ? $firstWallet : $secondWallet;
            $receiverWallet = $firstWallet->user_id === $receiverId ? $firstWallet : $secondWallet;

            // VALIDAÇÃO DE DOMÍNIO
            if ($senderWallet->balance < (float) $amount) {
                // Usando DomainException em vez de Exception genérica
                throw new \DomainException('Saldo insuficiente para realizar a transferência.');
            }

            $senderBalanceBefore = $senderWallet->balance;
            $receiverBalanceBefore = $receiverWallet->balance;

            // MUTAÇÃO
            $senderWallet->balance -= (float) $amount;
            $receiverWallet->balance += (float) $amount;

            $senderWallet->save();
            $receiverWallet->save();

            // LEDGER (Livro Razão)
            Transaction::create([
                'wallet_id' => $senderWallet->id,
                'amount' => $amount,
                'type' => 'debit',
                'payment_method_id' => 1,
                'idempotency_key' => $idempotencyKey . '-out',
                'metadata' => ['receiver_id' => $receiverWallet->id], // O cast no Model cuida do json_encode
                'balance_before' => $senderBalanceBefore,
                'balance_after' => $senderWallet->balance,
                'counterparty_id' => $receiverWallet->id,
            ]);

            Transaction::create([
                'wallet_id' => $receiverWallet->id,
                'amount' => $amount,
                'type' => 'credit',
                'payment_method_id' => 1,
                'idempotency_key' => $idempotencyKey . '-in',
                'metadata' => ['sender_id' => $senderWallet->id],
                'balance_before' => $receiverBalanceBefore,
                'balance_after' => $receiverWallet->balance,
                'counterparty_id' => $senderWallet->id,
            ]);

            // 3. Efetivação
            DB::commit();
            
            return true;

        } catch (QueryException $e) {
            // Desfaz tudo imediatamente
            DB::rollBack();
            
            // Intercepta violação de chave única (Idempotência falhou/Duplicidade)
            // Código 1062 é padrão do MySQL para Duplicate Entry
            if ($e->errorInfo[1] == 1062) {
                Log::warning("Tentativa de duplicidade na transferência", ['idempotency_key' => $idempotencyKey]);
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