<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function findByReferenceNumber(string $referenceNumber): ?Transaction
    {
        return $this->createQueryBuilder('t')
            ->where('t.referenceNumber = :referenceNumber')
            ->setParameter('referenceNumber', $referenceNumber)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAccountTransactions(Account $account, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.sourceAccount = :account OR t.destinationAccount = :account')
            ->setParameter('account', $account)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findPendingTransactions(int $limit = 100): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', Transaction::STATUS_PENDING)
            ->orderBy('t.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getTransactionStatistics(Account $account, \DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $qb = $this->createQueryBuilder('t');
        
        $outgoing = $qb
            ->select('COUNT(t.id) as count', 'SUM(t.amount) as total')
            ->where('t.sourceAccount = :account')
            ->andWhere('t.status = :status')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('account', $account)
            ->setParameter('status', Transaction::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleResult();

        $qb = $this->createQueryBuilder('t');
        $incoming = $qb
            ->select('COUNT(t.id) as count', 'SUM(t.amount) as total')
            ->where('t.destinationAccount = :account')
            ->andWhere('t.status = :status')
            ->andWhere('t.createdAt BETWEEN :from AND :to')
            ->setParameter('account', $account)
            ->setParameter('status', Transaction::STATUS_COMPLETED)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getSingleResult();

        return [
            'outgoing' => [
                'count' => $outgoing['count'] ?? 0,
                'total' => $outgoing['total'] ?? '0.0000',
            ],
            'incoming' => [
                'count' => $incoming['count'] ?? 0,
                'total' => $incoming['total'] ?? '0.0000',
            ],
        ];
    }
}
