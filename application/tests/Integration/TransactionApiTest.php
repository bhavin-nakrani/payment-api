<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TransactionApiTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;
    private string $authToken;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        
        // Clean up database
        $this->cleanDatabase();
        
        // Create test user and authenticate
        $this->authToken = $this->createAuthenticatedUser();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testCreateAccountSuccessfully(): void
    {
        $this->client->request(
            'POST',
            '/api/accounts',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
            ],
            json_encode([
                'accountType' => 'CHECKING',
                'currency' => 'USD',
                'initialBalance' => '1000.00',
            ])
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('account', $data);
        $this->assertEquals('CHECKING', $data['account']['accountType']);
        $this->assertEquals('USD', $data['account']['currency']);
        $this->assertNotEmpty($data['account']['accountNumber']);
    }

    public function testTransferFundsSuccessfully(): void
    {
        // Create source and destination accounts
        $sourceAccount = $this->createTestAccount('1000.00');
        $destinationAccount = $this->createTestAccount('500.00', false);

        $this->client->request(
            'POST',
            '/api/transactions/transfer',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
            ],
            json_encode([
                'sourceAccountNumber' => $sourceAccount->getAccountNumber(),
                'destinationAccountNumber' => $destinationAccount->getAccountNumber(),
                'amount' => '100.00',
                'description' => 'Test transfer',
            ])
        );

        $this->assertEquals(201, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('transaction', $data);
        $this->assertEquals('100.00', $data['transaction']['amount']);
        $this->assertEquals(Transaction::STATUS_PENDING, $data['transaction']['status']);
        $this->assertNotEmpty($data['transaction']['referenceNumber']);
    }

    public function testTransferWithInsufficientBalance(): void
    {
        $sourceAccount = $this->createTestAccount('50.00');
        $destinationAccount = $this->createTestAccount('500.00', false);

        $this->client->request(
            'POST',
            '/api/transactions/transfer',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken,
            ],
            json_encode([
                'sourceAccountNumber' => $sourceAccount->getAccountNumber(),
                'destinationAccountNumber' => $destinationAccount->getAccountNumber(),
                'amount' => '100.00',
            ])
        );

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
    }

    public function testGetTransactionDetails(): void
    {
        $sourceAccount = $this->createTestAccount('1000.00');
        $destinationAccount = $this->createTestAccount('500.00', false);

        $transaction = new Transaction();
        $transaction->setSourceAccount($sourceAccount);
        $transaction->setDestinationAccount($destinationAccount);
        $transaction->setAmount('100.00');
        $transaction->setCurrency('USD');
        $transaction->setDescription('Test transaction');

        $this->entityManager->persist($transaction);
        $this->entityManager->flush();

        $this->client->request(
            'GET',
            '/api/transactions/' . $transaction->getReferenceNumber(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertArrayHasKey('transaction', $data);
        $this->assertEquals($transaction->getReferenceNumber(), $data['transaction']['referenceNumber']);
        $this->assertEquals('100.0000', $data['transaction']['amount']);
    }

    public function testListAccountTransactions(): void
    {
        $account = $this->createTestAccount('1000.00');

        $this->client->request(
            'GET',
            '/api/transactions/account/' . $account->getAccountNumber(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->authToken]
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('transactions', $data);
        $this->assertIsArray($data['transactions']);
    }

    public function testUnauthorizedAccessWithoutToken(): void
    {
        $this->client->request('GET', '/api/accounts');
        $this->assertEquals(401, $this->client->getResponse()->getStatusCode());
    }

    private function createAuthenticatedUser(): string
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Login to get token
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'test@example.com',
                'password' => 'password123',
            ])
        );

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['token'];
    }

    private function createTestAccount(string $balance, bool $forCurrentUser = true): Account
    {
        if ($forCurrentUser) {
            $user = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => 'test@example.com']);
        } else {
            $user = new User();
            $user->setEmail('other@example.com');
            $user->setFirstName('Other');
            $user->setLastName('User');
            
            $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
            $user->setPassword($passwordHasher->hashPassword($user, 'password123'));
            
            $this->entityManager->persist($user);
        }

        $account = new Account();
        $account->setUser($user);
        $account->setBalance($balance);
        $account->setCurrency('USD');
        $account->setAccountType('CHECKING');

        $this->entityManager->persist($account);
        $this->entityManager->flush();

        return $account;
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        
        $tables = ['transactions', 'accounts', 'users'];
        
        foreach ($tables as $table) {
            try {
                $connection->executeStatement('DELETE FROM ' . $table);
            } catch (\Exception $e) {
                // Table might not exist yet
            }
        }
    }
}
