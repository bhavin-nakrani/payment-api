<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Predis\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Client $redis
    ) {
    }

    #[Route('/health', name: 'health_check', methods: ['GET'])]
    public function healthCheck(): JsonResponse
    {
        $status = 'healthy';
        $checks = [];

        // Database check
        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'failed';
            $status = 'unhealthy';
        }

        // Redis check
        try {
            $this->redis->ping();
            $checks['redis'] = 'ok';
        } catch (\Exception $e) {
            $checks['redis'] = 'failed';
            $status = 'unhealthy';
        }

        // Application check
        $checks['application'] = 'ok';

        return $this->json([
            'status' => $status,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'checks' => $checks,
        ], $status === 'healthy' ? 200 : 503);
    }

    #[Route('/health/live', name: 'health_liveness', methods: ['GET'])]
    public function liveness(): JsonResponse
    {
        return $this->json([
            'status' => 'alive',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);
    }

    #[Route('/health/ready', name: 'health_readiness', methods: ['GET'])]
    public function readiness(): JsonResponse
    {
        $ready = true;
        $checks = [];

        // Check database connection
        try {
            $this->connection->executeQuery('SELECT 1');
            $checks['database'] = 'ready';
        } catch (\Exception $e) {
            $checks['database'] = 'not_ready';
            $ready = false;
        }

        // Check Redis connection
        try {
            $this->redis->ping();
            $checks['redis'] = 'ready';
        } catch (\Exception $e) {
            $checks['redis'] = 'not_ready';
            $ready = false;
        }

        return $this->json([
            'status' => $ready ? 'ready' : 'not_ready',
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'checks' => $checks,
        ], $ready ? 200 : 503);
    }
}
