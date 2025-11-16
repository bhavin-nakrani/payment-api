<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\CompletedEvent;

#[AsEventListener(event: 'workflow.fund_transfer.completed')]
class TransactionWorkflowCompletedListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(CompletedEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();
        
        $this->logger->info('Transaction workflow transition completed', [
            'transaction_id' => $transaction->getId(),
            'reference' => $transaction->getReferenceNumber(),
            'from' => array_keys($event->getMarking()->getPlaces()),
            'transition' => $event->getTransition()->getName(),
        ]);
    }
}
