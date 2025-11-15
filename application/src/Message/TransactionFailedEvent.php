<?php

declare(strict_types=1);

namespace App\Message;

class TransactionFailedEvent
{
    public function __construct(
        private readonly string $transactionId,
        private readonly string $referenceNumber,
        private readonly string $reason
    ) {
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getReferenceNumber(): string
    {
        return $this->referenceNumber;
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
