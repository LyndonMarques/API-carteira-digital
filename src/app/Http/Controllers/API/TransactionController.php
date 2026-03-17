<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $this->walletService->transfer($request->toDTO());

            // Retornamos apenas a confirmação. Se precisar do recibo, 
            // o Service precisará ser alterado para retornar o objeto Transaction.
            return response()->json([
                'message' => 'Transferência realizada com sucesso.'
            ], 201);

        } catch (\InvalidArgumentException $e) {
            // Erro 400: O cliente enviou algo que a API não processa (ex: ID igual)
            return response()->json(['error' => $e->getMessage()], 400);

        } catch (\DomainException $e) {
            // Erro 422: Regra de negócio violada (Saldo insuficiente, duplicidade)
            return response()->json(['error' => $e->getMessage()], 422);

        } catch (\Throwable $e) {
            // Erro 500: O servidor falhou (Banco caiu, erro de sintaxe, etc)
            // 1. Registra o erro real no log para você debugar
            Log::error('Falha crítica na transferência: ' . $e->getMessage(), [
                'request' => $request->except('password'), // Nunca logue senhas
                'trace' => $e->getTraceAsString()
            ]);

            // 2. Retorna uma mensagem genérica e segura para o cliente
            return response()->json([
                'error' => 'Erro interno do servidor. A equipe técnica foi notificada.'
            ], 500);
        }
    }
}