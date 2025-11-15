<?php

declare(strict_types=1);

namespace App\Message;

class ProcessTransactionMessage
{
    public function __construct(
        private readonly string $transactionId
    ) {
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }
}
