<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\CreateAccountRequest;
use App\Entity\Account;
use App\Entity\User;
use App\Repository\AccountRepository;
use App\Service\CacheService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/accounts')]
class AccountController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AccountRepository $accountRepository,
        private readonly ValidatorInterface $validator,
        private readonly CacheService $cacheService,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('', name: 'api_account_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new CreateAccountRequest();
        $dto->accountType = $data['accountType'] ?? 'CHECKING';
        $dto->currency = $data['currency'] ?? 'USD';
        $dto->initialBalance = $data['initialBalance'] ?? '0.00';

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

        try {
            $account = new Account();
            $account->setUser($user);
            $account->setAccountType($dto->accountType);
            $account->setCurrency($dto->currency);
            $account->setBalance($dto->initialBalance);

            $this->entityManager->persist($account);
            $this->entityManager->flush();

            $this->logger->info('Account created successfully', [
                'account_id' => $account->getId()->toRfc4122(),
                'user_id' => $user->getId()->toRfc4122(),
                'account_number' => $account->getAccountNumber(),
            ]);

            return $this->json([
                'message' => 'Account created successfully',
                'account' => $this->serializeAccount($account),
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('Account creation failed', [
                'user_id' => $user->getId()->toRfc4122(),
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Account creation failed',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_account_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $accounts = $this->cacheService->getUserData(
            'accounts_' . $user->getId()->toRfc4122(),
            fn() => $this->accountRepository->findActiveAccountsByUser($user)
        );

        return $this->json([
            'accounts' => array_map(
                fn(Account $account) => $this->serializeAccount($account),
                $accounts
            ),
        ]);
    }

    #[Route('/{accountNumber}', name: 'api_account_get', methods: ['GET'])]
    public function get(string $accountNumber): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $account = $this->accountRepository->findActiveByAccountNumber($accountNumber);

        if (!$account) {
            return $this->json(['error' => 'Account not found'], Response::HTTP_NOT_FOUND);
        }

        // Verify ownership
        if ($account->getUser()->getId() !== $user->getId()) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $accountData = $this->cacheService->getAccountData(
            $account->getId()->toRfc4122(),
            fn() => $this->serializeAccount($account)
        );

        return $this->json(['account' => $accountData]);
    }

    #[Route('/{accountNumber}/balance', name: 'api_account_balance', methods: ['GET'])]
    public function getBalance(string $accountNumber): JsonResponse
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

        return $this->json([
            'accountNumber' => $account->getAccountNumber(),
            'balance' => $account->getBalance(),
            'currency' => $account->getCurrency(),
        ]);
    }

    private function serializeAccount(Account $account): array
    {
        return [
            'id' => $account->getId()->toRfc4122(),
            'accountNumber' => $account->getAccountNumber(),
            'accountType' => $account->getAccountType(),
            'balance' => $account->getBalance(),
            'currency' => $account->getCurrency(),
            'isActive' => $account->isActive(),
            'createdAt' => $account->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $account->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
