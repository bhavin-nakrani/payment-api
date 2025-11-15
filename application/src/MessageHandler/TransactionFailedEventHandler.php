<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\TransactionFailedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TransactionFailedEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TransactionFailedEvent $event): void
    {
        // Here you can add logic for:
        // - Sending failure notifications
        // - Alerting support team
        // - Logging to external monitoring system
        
        $this->logger->warning('Transaction failed event handled', [
            'transaction_id' => $event->getTransactionId(),
            'reference' => $event->getReferenceNumber(),
            'reason' => $event->getReason(),
        ]);

        // Example: Send failure alert
        // $this->alertService->sendTransactionFailureAlert($event);
    }
}
