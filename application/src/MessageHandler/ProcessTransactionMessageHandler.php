<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ProcessTransactionMessage;
use App\Message\TransactionCompletedEvent;
use App\Message\TransactionFailedEvent;
use App\Repository\TransactionRepository;
use App\Service\FundTransferService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class ProcessTransactionMessageHandler
{
    public function __construct(
        private readonly FundTransferService $fundTransferService,
        private readonly TransactionRepository $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(ProcessTransactionMessage $message): void
    {
        $transactionId = Uuid::fromString($message->getTransactionId());
        
        $this->logger->info('Processing transaction message', [
            'transaction_id' => $transactionId,
        ]);

        // Load transaction with all relationships eagerly
        $transaction = $this->transactionRepository->findWithRelations($message->getTransactionId());
        
        if (!$transaction) {
            $this->logger->error('Transaction not found for processing', [
                'transaction_id' => $transactionId,
            ]);
            return;
        }

        try {
            $this->fundTransferService->process($transaction);
            
            // Dispatch completion event
            $this->messageBus->dispatch(new TransactionCompletedEvent(
                $transaction->getId(),
                $transaction->getReferenceNumber(),
                $transaction->getAmount(),
                $transaction->getSourceAccount()->getAccountNumber(),
                $transaction->getDestinationAccount()->getAccountNumber()
            ));
            
        } catch (\Exception $e) {
            $this->logger->error('Transaction processing failed in handler', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Dispatch failure event
            $this->messageBus->dispatch(new TransactionFailedEvent(
                $transaction->getId(),
                $transaction->getReferenceNumber(),
                $e->getMessage()
            ));
            
            throw $e; // Re-throw to trigger retry mechanism
        }
    }
}
