<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Message\ProcessTransactionMessage;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class FundTransferService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $accountRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly WorkflowInterface $fundTransferStateMachine,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly CacheService $cacheService
    ) {
    }

    /**
     * Initiates a fund transfer between accounts
     * 
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function initiate(
        string $sourceAccountNumber,
        string $destinationAccountNumber,
        string $amount,
        ?string $description = null
    ): Transaction {
        $this->validateTransferRequest($sourceAccountNumber, $destinationAccountNumber, $amount);

        $this->entityManager->beginTransaction();
        
        try {
            $sourceAccount = $this->accountRepository->findActiveByAccountNumber($sourceAccountNumber);
            if (!$sourceAccount) {
                throw new \InvalidArgumentException('Source account not found or inactive');
            }

            $destinationAccount = $this->accountRepository->findActiveByAccountNumber($destinationAccountNumber);
            if (!$destinationAccount) {
                throw new \InvalidArgumentException('Destination account not found or inactive');
            }

            // Check if accounts have same currency
            if ($sourceAccount->getCurrency() !== $destinationAccount->getCurrency()) {
                throw new \InvalidArgumentException('Cross-currency transfers are not supported');
            }

            // Validate sufficient balance
            if (!$sourceAccount->hasEnoughBalance($amount)) {
                throw new \InvalidArgumentException('Insufficient balance');
            }

            // Create transaction
            $transaction = new Transaction();
            $transaction->setSourceAccount($sourceAccount);
            $transaction->setDestinationAccount($destinationAccount);
            $transaction->setAmount($amount);
            $transaction->setCurrency($sourceAccount->getCurrency());
            $transaction->setType(Transaction::TYPE_TRANSFER);
            $transaction->setDescription($description);

            $this->entityManager->persist($transaction);
            $this->entityManager->flush();

            // Dispatch to message queue for async processing
            $this->messageBus->dispatch(new ProcessTransactionMessage($transaction->getId()));

            $this->entityManager->commit();

            $this->logger->info('Fund transfer initiated', [
                'transaction_id' => $transaction->getId(),
                'reference' => $transaction->getReferenceNumber(),
                'amount' => $amount,
                'from' => $sourceAccountNumber,
                'to' => $destinationAccountNumber,
            ]);

            // Invalidate cache
            $this->cacheService->invalidateAccountCache($sourceAccount->getId());
            $this->cacheService->invalidateAccountCache($destinationAccount->getId());

            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            $this->logger->error('Fund transfer initiation failed', [
                'error' => $e->getMessage(),
                'from' => $sourceAccountNumber,
                'to' => $destinationAccountNumber,
                'amount' => $amount,
            ]);
            
            throw $e;
        }
    }

    /**
     * Process transaction - called by message handler
     */
    public function process(Transaction $transaction): void
    {
        if (!$this->fundTransferStateMachine->can($transaction, 'process')) {
            throw new \RuntimeException('Transaction cannot be processed in current state');
        }

        $this->entityManager->beginTransaction();
        
        try {
            // Refresh transaction to ensure we have latest state with proper relationships
            $this->entityManager->refresh($transaction);
            
            // Lock accounts to prevent concurrent modifications
            $sourceAccount = $this->accountRepository->findByIdWithLock($transaction->getSourceAccount()->getId());
            $destinationAccount = $this->accountRepository->findByIdWithLock($transaction->getDestinationAccount()->getId());

            if (!$sourceAccount || !$destinationAccount) {
                throw new \RuntimeException('Account not found');
            }

            // Apply workflow transition
            $this->fundTransferStateMachine->apply($transaction, 'process');
            
            // Double-check balance (defensive programming)
            if (!$sourceAccount->hasEnoughBalance($transaction->getAmount())) {
                $this->fundTransferStateMachine->apply($transaction, 'fail');
                $transaction->setFailureReason('Insufficient balance during processing');
                $this->entityManager->flush();
                $this->entityManager->commit();
                return;
            }

            // Perform the actual transfer
            $sourceAccount->debit($transaction->getAmount());
            $destinationAccount->credit($transaction->getAmount());

            // Complete transaction
            $this->fundTransferStateMachine->apply($transaction, 'complete');
            $transaction->setCompletedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Fund transfer completed', [
                'transaction_id' => $transaction->getId(),
                'reference' => $transaction->getReferenceNumber(),
            ]);

            // Invalidate cache
            $this->cacheService->invalidateAccountCache($sourceAccount->getId());
            $this->cacheService->invalidateAccountCache($destinationAccount->getId());

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            // Mark as failed
            $this->entityManager->beginTransaction();
            try {
                if ($this->fundTransferStateMachine->can($transaction, 'fail')) {
                    $this->fundTransferStateMachine->apply($transaction, 'fail');
                    $transaction->setFailureReason($e->getMessage());
                    $this->entityManager->flush();
                }
                $this->entityManager->commit();
            } catch (\Exception $innerException) {
                $this->entityManager->rollback();
                $this->logger->error('Failed to mark transaction as failed', [
                    'error' => $innerException->getMessage(),
                    'transaction_id' => $transaction->getId(),
                ]);
            }

            $this->logger->error('Fund transfer processing failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->getId(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Reverse a completed transaction
     */
    public function reverse(Transaction $transaction, string $reason): Transaction
    {
        if (!$this->fundTransferStateMachine->can($transaction, 'reverse')) {
            throw new \RuntimeException('Transaction cannot be reversed in current state');
        }

        $this->entityManager->beginTransaction();
        
        try {
            $sourceAccount = $this->accountRepository->findByIdWithLock($transaction->getSourceAccount()->getId());
            $destinationAccount = $this->accountRepository->findByIdWithLock($transaction->getDestinationAccount()->getId());

            // Reverse the amounts
            $sourceAccount->credit($transaction->getAmount());
            $destinationAccount->debit($transaction->getAmount());

            // Apply workflow transition
            $this->fundTransferStateMachine->apply($transaction, 'reverse');
            $transaction->setFailureReason($reason);

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->logger->info('Fund transfer reversed', [
                'transaction_id' => $transaction->getId(),
                'reason' => $reason,
            ]);

            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            
            $this->logger->error('Fund transfer reversal failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->getId(),
            ]);
            
            throw $e;
        }
    }

    public function getTransactionByReference(string $referenceNumber): ?Transaction
    {
        return $this->transactionRepository->findByReferenceNumber($referenceNumber);
    }

    private function validateTransferRequest(string $source, string $destination, string $amount): void
    {
        if ($source === $destination) {
            throw new \InvalidArgumentException('Source and destination accounts must be different');
        }

        if (bccomp($amount, '0', 4) <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if (bccomp($amount, '1000000', 4) > 0) {
            throw new \InvalidArgumentException('Amount exceeds maximum transfer limit');
        }
    }
}
