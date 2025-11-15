<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\RegisterUserRequest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/register', name: 'api_auth_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->json(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $dto = new RegisterUserRequest();
        $dto->email = $data['email'] ?? '';
        $dto->password = $data['password'] ?? '';
        $dto->firstName = $data['firstName'] ?? '';
        $dto->lastName = $data['lastName'] ?? '';

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

        // Check if user already exists
        if ($this->userRepository->findOneBy(['email' => $dto->email])) {
            return $this->json(['error' => 'User already exists'], Response::HTTP_CONFLICT);
        }

        try {
            $user = new User();
            $user->setEmail($dto->email);
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->password));
            $user->setFirstName($dto->firstName);
            $user->setLastName($dto->lastName);
            $user->setRoles(['ROLE_USER']);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('User registered successfully', [
                'user_id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
            ]);

            return $this->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->getId()->toRfc4122(),
                    'email' => $user->getEmail(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                ],
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logger->error('User registration failed', [
                'email' => $dto->email,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Registration failed',
                'message' => 'An error occurred during registration',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/me', name: 'api_auth_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->json([
            'user' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
                'isActive' => $user->isActive(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ],
        ]);
    }
}
