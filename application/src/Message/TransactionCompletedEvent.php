<?php

declare(strict_types=1);

namespace App\Message;

class TransactionCompletedEvent
{
    public function __construct(
        private readonly string $transactionId,
        private readonly string $referenceNumber,
        private readonly string $amount,
        private readonly string $sourceAccountNumber,
        private readonly string $destinationAccountNumber
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

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getSourceAccountNumber(): string
    {
        return $this->sourceAccountNumber;
    }

    public function getDestinationAccountNumber(): string
    {
        return $this->destinationAccountNumber;
    }
}
