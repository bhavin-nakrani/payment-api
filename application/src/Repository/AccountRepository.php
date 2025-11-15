<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Account>
 */
class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function findActiveByAccountNumber(string $accountNumber): ?Account
    {
        return $this->createQueryBuilder('a')
            ->where('a.accountNumber = :accountNumber')
            ->andWhere('a.isActive = :active')
            ->setParameter('accountNumber', $accountNumber)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByIdWithLock(Uuid $id): ?Account
    {
        return $this->createQueryBuilder('a')
            ->where('a.id = :id')
            ->setParameter('id', $id, 'uuid')
            ->getQuery()
            ->setLockMode(\Doctrine\DBAL\LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findActiveAccountsByUser(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('active', true)
            ->orderBy('a.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalBalance(User $user, string $currency = 'USD'): string
    {
        $result = $this->createQueryBuilder('a')
            ->select('SUM(a.balance) as total')
            ->where('a.user = :user')
            ->andWhere('a.currency = :currency')
            ->andWhere('a.isActive = :active')
            ->setParameter('user', $user)
            ->setParameter('currency', $currency)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ?? '0.0000';
    }
}
