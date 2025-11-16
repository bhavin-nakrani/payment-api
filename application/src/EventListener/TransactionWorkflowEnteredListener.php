<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Transaction;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

#[AsEventListener(event: 'workflow.fund_transfer.entered')]
class TransactionWorkflowEnteredListener
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(EnteredEvent $event): void
    {
        /** @var Transaction $transaction */
        $transaction = $event->getSubject();
        
        $this->logger->info('Transaction workflow state entered', [
            'transaction_id' => $transaction->getId(),
            'reference' => $transaction->getReferenceNumber(),
            'state' => $event->getMarking()->getPlaces(),
            'transition' => $event->getTransition()?->getName(),
        ]);
    }
}
