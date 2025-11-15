<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\TransferFundsRequest;
use App\Entity\Account;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\FundTransferService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    public function __construct(
        private readonly FundTransferService $fundTransferService,
        private readonly TransactionRepository $transactionRepository,
        private readonly AccountRepository $accountRepository,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/transfer', name: 'api_transaction_transfer', methods: ['POST'])]
    public function transfer(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new TransferFundsRequest();
        $dto->sourceAccountNumber = $data['sourceAccountNumber'] ?? '';
        $dto->destinationAccountNumber = $data['destinationAccountNumber'] ?? '';
        $dto->amount = $data['amount'] ?? '';
        $dto->description = $data['description'] ?? null;

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json([
                'error' => 'Validation failed',
                'violations' => array_map(fn($error) => [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ], iterator_to_array($errors)),
            ], Response::HTTP_BAD_REQUEST);
        }

        // Verify source account ownership
        $sourceAccount = $this->accountRepository->findActiveByAccountNumber($dto->sourceAccountNumber);
        if (!$sourceAccount) {
            return $this->json(['error' => 'Source account not found'], Response::HTTP_NOT_FOUND);
        }

        if ($sourceAccount->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'You do not own the source account'], Response::HTTP_FORBIDDEN);
        }

        try {
            $transaction = $this->fundTransferService->initiate(
                $dto->sourceAccountNumber,
                $dto->destinationAccountNumber,
                $dto->amount,
                $dto->description
            );

            return $this->json([
                'message' => 'Transfer initiated successfully',
                'transaction' => [
                    'id' => $transaction->getId()->toRfc4122(),
                    'referenceNumber' => $transaction->getReferenceNumber(),
                    'status' => $transaction->getStatus(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                    'sourceAccount' => $transaction->getSourceAccount()->getAccountNumber(),
                    'destinationAccount' => $transaction->getDestinationAccount()->getAccountNumber(),
                    'description' => $transaction->getDescription(),
                    'createdAt' => $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
                ],
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'error' => 'Transfer validation failed',
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Transfer initiation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()->toRfc4122(),
            ]);

            return $this->json([
                'error' => 'Transfer failed',
                'message' => 'An error occurred while processing the transfer',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{referenceNumber}', name: 'api_transaction_get', methods: ['GET'])]
    public function get(string $referenceNumber): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $transaction = $this->fundTransferService->getTransactionByReference($referenceNumber);

        if (!$transaction) {
            return $this->json(['error' => 'Transaction not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify user has access (owns source or destination account)
        $hasAccess = $transaction->getSourceAccount()->getUser()->getId() === $user->getId()
            || $transaction->getDestinationAccount()->getUser()->getId() === $user->getId();

        if (!$hasAccess) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return $this->json([
            'transaction' => [
                'id' => $transaction->getId()->toRfc4122(),
                'referenceNumber' => $transaction->getReferenceNumber(),
                'status' => $transaction->getStatus(),
                'type' => $transaction->getType(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'sourceAccount' => $transaction->getSourceAccount()->getAccountNumber(),
                'destinationAccount' => $transaction->getDestinationAccount()->getAccountNumber(),
                'description' => $transaction->getDescription(),
                'failureReason' => $transaction->getFailureReason(),
                'createdAt' => $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'updatedAt' => $transaction->getUpdatedAt()->format(\DateTimeInterface::ATOM),
                'completedAt' => $transaction->getCompletedAt()?->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }

    #[Route('/account/{accountNumber}', name: 'api_transaction_list_by_account', methods: ['GET'])]
    public function listByAccount(string $accountNumber, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $account = $this->accountRepository->findActiveByAccountNumber($accountNumber);

        if (!$account) {
            return $this->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        if ($account->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $limit = min((int) $request->query->get('limit', 50), 100);
        $transactions = $this->transactionRepository->findAccountTransactions($account, $limit);

        return $this->json([
            'transactions' => array_map(fn($transaction) => [
                'id' => $transaction->getId()->toRfc4122(),
                'referenceNumber' => $transaction->getReferenceNumber(),
                'status' => $transaction->getStatus(),
                'type' => $transaction->getType(),
                'amount' => $transaction->getAmount(),
                'currency' => $transaction->getCurrency(),
                'sourceAccount' => $transaction->getSourceAccount()->getAccountNumber(),
                'destinationAccount' => $transaction->getDestinationAccount()->getAccountNumber(),
                'description' => $transaction->getDescription(),
                'createdAt' => $transaction->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ], $transactions),
        ]);
    }

    #[Route('/account/{accountNumber}/statistics', name: 'api_transaction_statistics', methods: ['GET'])]
    public function statistics(string $accountNumber, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $account = $this->accountRepository->findActiveByAccountNumber($accountNumber);

        if (!$account) {
            return $this->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        if ($account->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $from = new \DateTimeImmutable($request->query->get('from', '-30 days'));
        $to = new \DateTimeImmutable($request->query->get('to', 'now'));

        $statistics = $this->transactionRepository->getTransactionStatistics($account, $from, $to);

        return $this->json([
            'accountNumber' => $accountNumber,
            'period' => [
                'from' => $from->format(\DateTimeInterface::ATOM),
                'to' => $to->format(\DateTimeInterface::ATOM),
            ],
            'statistics' => $statistics,
        ]);
    }
}
