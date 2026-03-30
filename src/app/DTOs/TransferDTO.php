<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class TransferDTO
{
    public function __construct(
        public int $senderId,
        public int $receiverId,
        public float $amount,
        public string $idempotencyKey,
        public int $paymentMethodId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            senderId: (int) $data['sender_id'],
            receiverId: (int) $data['receiver_id'],
            amount: (float) $data['amount'],
            idempotencyKey: (string) $data['idempotency_key'],
            paymentMethodId: (int) $data['payment_method_id'],
        );
    }
}