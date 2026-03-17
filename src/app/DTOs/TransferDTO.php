<?php

namespace App\DTOs;

readonly class TransferDTO
{
    public function __construct(
        public int $senderId,
        public int $receiverId,
        public float $amount,
        public string $idempotencyKey,
        public int $paymentMethodId
    ) {}
}