<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'amount',
        'type',
        'payment_method_id',
        'idempotency_key',
        'metadata',
        'balance_before',
        'balance_after',
        'counterparty_id'
    ];

    /**
     * O cast automático garante que o JSON do metadata vire um array no PHP
     * e que os valores financeiros sejam tratados como decimais.
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }
}