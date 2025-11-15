<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\User;
use App\Message\ProcessTransactionMessage;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\CacheService;
use App\Service\FundTransferService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Workflow\WorkflowInterface;

class FundTransferServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private AccountRepository $accountRepository;
    private TransactionRepository $transactionRepository;
    private WorkflowInterface $workflow;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private CacheService $cacheService;
    private FundTransferService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->accountRepository = $this->createMock(AccountRepository::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->workflow = $this->createMock(WorkflowInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cacheService = $this->createMock(CacheService::class);

        $this->service = new FundTransferService(
            $this->entityManager,
            $this->accountRepository,
            $this->transactionRepository,
            $this->workflow,
            $this->messageBus,
            $this->logger,
            $this->cacheService
        );
    }

    public function testInitiateSuccessfulTransfer(): void
    {
        $sourceAccount = $this->createAccount('12345678901234567890', '1000.00');
        $destinationAccount = $this->createAccount('09876543210987654321', '500.00');

        $this->accountRepository
            ->expects($this->exactly(2))
            ->method('findActiveByAccountNumber')
            ->willReturnOnConsecutiveCalls($sourceAccount, $destinationAccount);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Transaction::class));

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->messageBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ProcessTransactionMessage::class));

        $transaction = $this->service->initiate(
            '12345678901234567890',
            '09876543210987654321',
            '100.00',
            'Test transfer'
        );

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals('100.00', $transaction->getAmount());
        $this->assertEquals(Transaction::STATUS_PENDING, $transaction->getStatus());
    }

    public function testInitiateWithInsufficientBalance(): void
    {
        $sourceAccount = $this->createAccount('12345678901234567890', '50.00');
        $destinationAccount = $this->createAccount('09876543210987654321', '500.00');

        $this->accountRepository
            ->expects($this->exactly(2))
            ->method('findActiveByAccountNumber')
            ->willReturnOnConsecutiveCalls($sourceAccount, $destinationAccount);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->service->initiate(
            '12345678901234567890',
            '09876543210987654321',
            '100.00'
        );
    }

    public function testInitiateWithSameSourceAndDestination(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Source and destination accounts must be different');

        $this->service->initiate(
            '12345678901234567890',
            '12345678901234567890',
            '100.00'
        );
    }

    public function testInitiateWithInvalidAmount(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount must be greater than zero');

        $this->service->initiate(
            '12345678901234567890',
            '09876543210987654321',
            '-100.00'
        );
    }

    public function testProcessTransaction(): void
    {
        $sourceAccount = $this->createAccount('12345678901234567890', '1000.00');
        $destinationAccount = $this->createAccount('09876543210987654321', '500.00');
        
        $transaction = new Transaction();
        $transaction->setSourceAccount($sourceAccount);
        $transaction->setDestinationAccount($destinationAccount);
        $transaction->setAmount('100.00');
        $transaction->setCurrency('USD');
        $transaction->setStatus(Transaction::STATUS_PENDING);

        $this->workflow
            ->expects($this->once())
            ->method('can')
            ->with($transaction, 'process')
            ->willReturn(true);

        $this->workflow
            ->expects($this->exactly(2))
            ->method('apply')
            ->withConsecutive(
                [$transaction, 'process'],
                [$transaction, 'complete']
            );

        $this->accountRepository
            ->expects($this->exactly(2))
            ->method('findByIdWithLock')
            ->willReturnOnConsecutiveCalls($sourceAccount, $destinationAccount);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $this->entityManager
            ->expects($this->once())
            ->method('commit');

        $this->service->process($transaction);

        $this->assertEquals('900.0000', $sourceAccount->getBalance());
        $this->assertEquals('600.0000', $destinationAccount->getBalance());
        $this->assertTrue($transaction->isCompleted());
    }

    private function createAccount(string $accountNumber, string $balance): Account
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        $user->setFirstName('Test');
        $user->setLastName('User');

        $account = new Account();
        $account->setAccountNumber($accountNumber);
        $account->setBalance($balance);
        $account->setCurrency('USD');
        $account->setUser($user);
        $account->setIsActive(true);

        return $account;
    }
}
