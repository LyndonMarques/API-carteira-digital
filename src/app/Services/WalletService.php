<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class WalletService
{
    /**
     * Processa uma transferência atômica P2P (Peer-to-Peer).
     */
    public function transfer(int $senderId, int $receiverId, string $amount, string $idempotencyKey): bool
    {
        // Impede que um usuário transfira para si mesmo
        if ($senderId === $receiverId) {
            throw new Exception('Operação inválida.');
        }

        return DB::transaction(function () use ($senderId, $receiverId, $amount, $idempotencyKey) {
            
            // 1. PREVENÇÃO DE DEADLOCK: Ordena os IDs para travar sempre na mesma sequência
            $firstLockId = min($senderId, $receiverId);
            $secondLockId = max($senderId, $receiverId);

            Wallet::lockForUpdate()->findOrFail($firstLockId);
            Wallet::lockForUpdate()->findOrFail($secondLockId);

            // 2. Agora busca as instâncias reais (já estão com lock no banco)
            $senderWallet = Wallet::findOrFail($senderId);
            $receiverWallet = Wallet::findOrFail($receiverId);

            // 3. Validação
            if ($senderWallet->balance < (float) $amount) {
                throw new Exception('Saldo insuficiente.');
            }

            // Captura o estado ANTES da mutação para o rastro limpo
            $senderBalanceBefore = $senderWallet->balance;
            $receiverBalanceBefore = $receiverWallet->balance;

            // 4. Mutação
            $senderWallet->balance -= (float) $amount;
            $receiverWallet->balance += (float) $amount;
            
            $senderWallet->save();
            $receiverWallet->save();

            // 5. O Livro Razão (Ledger) com chaves de idempotência únicas por operação
            Transaction::create([
                'wallet_id' => $senderWallet->id,
                'amount' => $amount,
                'type' => 'debit',
                'payment_method_id' => 1, // Assumindo que 1 é "Transferência Interna". Ajuste a seu critério.
                'idempotency_key' => $idempotencyKey . '-out',
                'metadata' => json_encode(['receiver_id' => $receiverWallet->id]),
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
                'metadata' => json_encode(['sender_id' => $senderWallet->id]),
                'balance_before' => $receiverBalanceBefore,
                'balance_after' => $receiverWallet->balance,
                'counterparty_id' => $senderWallet->id,
            ]);

            return true;
        });
    }
}