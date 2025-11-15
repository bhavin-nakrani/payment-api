<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class CacheService
{
    private const ACCOUNT_CACHE_PREFIX = 'account_';
    private const ACCOUNT_CACHE_TTL = 300; // 5 minutes
    private const USER_CACHE_PREFIX = 'user_';
    private const USER_CACHE_TTL = 600; // 10 minutes

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getAccountData(string $accountId, callable $callback): mixed
    {
        $cacheKey = self::ACCOUNT_CACHE_PREFIX . $accountId;
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(self::ACCOUNT_CACHE_TTL);
                return $callback();
            });
        } catch (\Exception $e) {
            $this->logger->error('Cache get failed for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    public function invalidateAccountCache(string $accountId): void
    {
        $cacheKey = self::ACCOUNT_CACHE_PREFIX . $accountId;
        
        try {
            $this->cache->delete($cacheKey);
        } catch (\Exception $e) {
            $this->logger->error('Cache invalidation failed for account', [
                'account_id' => $accountId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getUserData(string $userId, callable $callback): mixed
    {
        $cacheKey = self::USER_CACHE_PREFIX . $userId;
        
        try {
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($callback) {
                $item->expiresAfter(self::USER_CACHE_TTL);
                return $callback();
            });
        } catch (\Exception $e) {
            $this->logger->error('Cache get failed for user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $callback();
        }
    }

    public function invalidateUserCache(string $userId): void
    {
        $cacheKey = self::USER_CACHE_PREFIX . $userId;
        
        try {
            $this->cache->delete($cacheKey);
        } catch (\Exception $e) {
            $this->logger->error('Cache invalidation failed for user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function clearAll(): void
    {
        try {
            $this->cache->clear();
            $this->logger->info('All cache cleared');
        } catch (\Exception $e) {
            $this->logger->error('Failed to clear cache', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
