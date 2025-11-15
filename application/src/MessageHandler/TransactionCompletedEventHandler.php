<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\TransactionCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class TransactionCompletedEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(TransactionCompletedEvent $event): void
    {
        // Here you can add logic for:
        // - Sending notifications
        // - Updating analytics
        // - Triggering webhooks
        // - Sending emails
        
        $this->logger->info('Transaction completed event handled', [
            'transaction_id' => $event->getTransactionId(),
            'reference' => $event->getReferenceNumber(),
            'amount' => $event->getAmount(),
            'from' => $event->getSourceAccountNumber(),
            'to' => $event->getDestinationAccountNumber(),
        ]);

        // Example: Send notification (implement your notification logic)
        // $this->notificationService->sendTransactionCompletedNotification($event);
    }
}
