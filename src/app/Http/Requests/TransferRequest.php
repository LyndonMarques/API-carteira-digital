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
}