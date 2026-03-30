<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferRequest;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    public function __construct(
        protected readonly WalletService $walletService
    ) {}

    public function transfer(TransferRequest $request): JsonResponse
    {
        // O request já foi validado pelo FormRequest.
        // O DTO blindou a tipagem.
        // O Service executa a lógica.
        $this->walletService->transfer($request->toDTO());

        // Se chegou aqui, é sucesso absoluto.
        return response()->json([
            'message' => 'Transferência realizada com sucesso.'
        ], 201);
    }
}