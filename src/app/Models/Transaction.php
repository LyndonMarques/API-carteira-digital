<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
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