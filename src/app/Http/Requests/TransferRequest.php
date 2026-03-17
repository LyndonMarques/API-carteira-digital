<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Em produção, aqui verificaríamos se o usuário logado é o dono da carteira
    }

    public function rules(): array
    {
        return [
            'sender_id'       => 'required|integer|exists:wallets,user_id',
            'receiver_id'     => 'required|integer|exists:wallets,user_id|different:sender_id',
            'amount'          => 'required|numeric|min:0.01',
            'idempotency_key' => 'required|string', 
            'payment_method_id' => 'required|integer|exists:payment_methods,id', // Validação rigorosa
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.different' => 'Operação bloqueada: você não pode transferir para si mesmo.',
            'amount.min'            => 'Operação bloqueada: o valor mínimo é R$ 0,01.',
            'receiver_id.exists'    => 'Operação bloqueada: a carteira de destino não existe.',
        ];
    }

    /**
     * Transforma os dados validados em um DTO imutável.
     */
    public function toDTO(): \App\DTOs\TransferDTO
    {
        return new \App\DTOs\TransferDTO(
            (int) $this->validated('sender_id'),
            (int) $this->validated('receiver_id'),
            (float) $this->validated('amount'),
            (string) $this->validated('idempotency_key'),
            (int) $this->validated('payment_method_id') // Propagação
        );
    }
}