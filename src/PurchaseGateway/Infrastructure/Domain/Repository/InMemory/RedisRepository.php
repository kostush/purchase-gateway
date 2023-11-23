<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory;

use Illuminate\Support\Facades\App;
use ProBillerNG\Logger\Log;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\InMemoryRepository;
use Redis;

class RedisRepository implements InMemoryRepository
{
    /**
     * @var Redis|null
     */
    private $redis;

    /**
     * RedisRepository constructor.
     * @throws Exception
     */
    public function __construct()
    {
        try {
            $this->redis = App::make(Redis::class);
        } catch (\Throwable $e) {
            Log::error(
                "RedisPurchaseStatus Connection to Redis failed.",
                [
                    "errorMessage" => $e->getMessage(),
                ]
            );
            $this->redis = null;
        }
    }

    /**
     * @param string $sessionId Session id
     * @return bool
     * @throws Exception
     */
    public function storeSessionId(string $sessionId): bool
    {
        $redisKey    = $this->getSessionIdKey($sessionId);
        $isKeyStored = $this->redis->setnx($redisKey, $sessionId);

        if ($isKeyStored) {
            Log::info(
                "RedisSessionId Redis key saved for concurrent calls.",
                [
                    [
                        'key'       => $redisKey,
                        'sessionId' => $sessionId,
                    ],
                ]
            );
        } else {
            Log::warning(
                "RedisSessionId Redis key for concurrent calls was not saved because it already exists.",
                [
                    'key'              => $redisKey,
                    'sessionId'        => $sessionId,
                    'connectionStatus' => $this->isConnected(),
                ]
            );
        }

        return $isKeyStored;
    }

    /**
     * @param string $sessionId Session id
     *
     * @return int
     * @throws Exception
     */
    public function deleteSessionId(string $sessionId): int
    {
        $redisKey   = $this->getSessionIdKey($sessionId);
        $keyDeleted = $this->redis->del($redisKey);

        if ($keyDeleted) {
            Log::info(
                "RedisSessionId Redis key has been deleted.",
                [
                    [
                        'key'     => $redisKey,
                        'deleted' => $keyDeleted,
                    ],
                ]
            );

            return $keyDeleted;
        }

        Log::warning(
            "RedisSessionId Redis key failed to be deleted.",
            [
                'key'              => $redisKey,
                'connectionStatus' => $this->isConnected(),
                'deleted'          => $keyDeleted,
            ]
        );

        return $keyDeleted;
    }

    /**
     * @param string $sessionId Session id
     * @param string $status    Status to store
     *
     * @return bool
     * @throws Exception
     */
    public function storePurchaseStatus(string $sessionId, string $status): bool
    {
        $redisKey = $this->getPurchaseStatusKey($sessionId);
        $ttl      = ['nx', 'ex' => (int) env('REDIS_RECORD_TTL', 900)];

        $isKeyStored = $this->redis->set($redisKey, $status, $ttl);

        if ($isKeyStored) {
            Log::info(
                "RedisPurchaseStatus Redis key saved.",
                [
                    [
                        'key'    => $redisKey,
                        'status' => $status,
                    ],
                ]
            );
        } else {
            Log::warning(
                "RedisPurchaseStatus Redis key failed to be saved.",
                [
                    'key'              => $redisKey,
                    'status'           => $status,
                    'connectionStatus' => $this->isConnected(),
                ]
            );
        }

        return (bool) $isKeyStored;
    }

    /**
     * @param string $sessionId Session Id
     *
     * @return string
     * @throws Exception
     */
    public function retrievePurchaseStatus(string $sessionId): string
    {
        $redisKey     = $this->getPurchaseStatusKey($sessionId);
        $keyRetrieved = $this->redis->get($redisKey);

        if ($keyRetrieved) {
            Log::info(
                "RedisPurchaseStatus Redis key retrieved.",
                [
                    [
                        'key'    => $redisKey,
                        'status' => $keyRetrieved,
                    ],
                ]
            );

            return $keyRetrieved;
        }

        Log::warning(
            "RedisPurchaseStatus Redis key not found.",
            [
                [
                    'key'    => $redisKey,
                    'status' => $keyRetrieved,
                ],
            ]
        );

        return (string) $keyRetrieved;
    }

    /**
     * @param string $sessionId Session Id
     *
     * @return int
     * @throws Exception
     */
    public function deletePurchaseStatus(string $sessionId): int
    {
        $redisKey   = $this->getPurchaseStatusKey($sessionId);
        $keyDeleted = $this->redis->del($redisKey);

        if ($keyDeleted) {
            Log::info(
                "RedisPurchaseStatus Redis key has been deleted.",
                [
                    [
                        'key'     => $redisKey,
                        'deleted' => $keyDeleted,
                    ],
                ]
            );
            return $keyDeleted;
        }

        Log::warning(
            "RedisPurchaseStatus Redis key failed to be deleted.",
            [
                'key'              => $redisKey,
                'connectionStatus' => $this->isConnected(),
                'deleted'          => $keyDeleted,
            ]
        );

        return (int) $keyDeleted;
    }

    /**
     * @param string $sessionId           Session id.
     * @param int    $gatewaySubmitNumber Gateway submit number.
     * @return bool
     * @throws Exception
     */
    public function storeGatewaySubmitNumber(string $sessionId, int $gatewaySubmitNumber): bool
    {
        $redisKey = $this->getGatewaySubmitNumberKey($sessionId);
        $ttl      = ['ex' => (int) env('REDIS_RECORD_TTL', 900)];

        $isKeyStored = $this->redis->set($redisKey, $gatewaySubmitNumber, $ttl);

        if ($isKeyStored) {
            Log::info(
                "RedisGatewaySubmitNumber Redis key saved.",
                [
                    [
                        'key'                 => $redisKey,
                        'gatewaySubmitNumber' => $gatewaySubmitNumber,
                    ],
                ]
            );
        } else {
            Log::warning(
                "RedisGatewaySubmitNumber Redis key failed to be saved.",
                [
                    'key'                 => $redisKey,
                    'gatewaySubmitNumber' => $gatewaySubmitNumber,
                    'connectionStatus'    => $this->isConnected(),
                ]
            );
        }

        return (bool) $isKeyStored;
    }

    /**
     * @param string $sessionId Session Id.
     *
     * @return string
     * @throws Exception
     */
    public function retrieveGatewaySubmitNumber(string $sessionId): string
    {
        $redisKey = $this->getGatewaySubmitNumberKey($sessionId);
        $value    = $this->redis->get($redisKey);

        if ($value) {
            Log::info(
                "RedisGatewaySubmitNumber Redis key retrieved.",
                [
                    [
                        'key'                 => $redisKey,
                        'gatewaySubmitNumber' => $value,
                    ],
                ]
            );
        } else {
            Log::warning(
                "RedisGatewaySubmitNumber Redis key not found.",
                [
                    [
                        'key'    => $redisKey,
                        'gatewaySubmitNumber' => $value,
                    ],
                ]
            );
        }

        return (string) $value;
    }

    /**
     * @param string $sessionId Session Id.
     *
     * @return int
     * @throws Exception
     */
    public function deleteGatewaySubmitNumber(string $sessionId): int
    {
        $redisKey   = $this->getGatewaySubmitNumberKey($sessionId);
        $keyDeleted = $this->redis->del($redisKey);

        if ($keyDeleted) {
            Log::info(
                "RedisGatewaySubmitNumber Redis key has been deleted.",
                [
                    [
                        'key'     => $redisKey,
                        'deleted' => $keyDeleted,
                    ],
                ]
            );
        } else {
            Log::warning(
                "RedisGatewaySubmitNumber Redis key failed to be deleted.",
                [
                    'key'              => $redisKey,
                    'connectionStatus' => $this->isConnected(),
                    'deleted'          => $keyDeleted,
                ]
            );
        }

        return (int) $keyDeleted;
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        if ($this->redis instanceof Redis) {
            return $this->redis->isConnected();
        }

        return false;
    }

    /**
     * @param string $sessionId Session id
     *
     * @return string
     */
    private function getPurchaseStatusKey(string $sessionId): string
    {
        return "session:" . $sessionId;
    }

    /**
     * @param string $sessionId Session id
     *
     * @return string
     */
    private function getSessionIdKey(string $sessionId): string
    {
        return "sessionId:" . $sessionId;
    }

    /**
     * @param string $sessionId Session id.
     *
     * @return string
     */
    private function getGatewaySubmitNumberKey(string $sessionId): string
    {
        return "GatewaySubmitNumberKey" . $sessionId;
    }
}
