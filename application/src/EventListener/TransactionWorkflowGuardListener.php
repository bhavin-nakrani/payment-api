<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\GuardEvent;

#[AsEventListener(event: 'workflow.fund_transfer.guard')]
class TransactionWorkflowGuardListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(GuardEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();
        
        // Add custom business logic guards here
        // For example, you might want to prevent processing if accounts are frozen
        
        $this->logger->debug('Transaction workflow guard check', [
            'transaction_id' => $transaction->getId(),
            'transition' => $event->getTransition()->getName(),
        ]);
    }
}
