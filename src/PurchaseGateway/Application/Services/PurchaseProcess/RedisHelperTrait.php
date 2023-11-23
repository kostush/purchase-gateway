<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;

/**
 * Trait RedisHelperTrait
 * @package ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess
 */
trait RedisHelperTrait
{
    /**
     * @param string $sessionId Session id
     *
     * @throws DuplicatedPurchaseProcessRequestException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function isProcessAlreadyStarted(string $sessionId)
    {
        try {
            Log::info('RedisPurchaseStatus Start checking Redis for request already processing.');

            $redisKey = $this->redisRepository->retrievePurchaseStatus($sessionId);

            if (!empty($redisKey) && $redisKey == Processing::name()) {
                Log::error(
                    "RedisPurchaseStatus Duplicated purchase request.",
                    [
                        "status"    => Processing::name(),
                    ]
                );
                throw new DuplicatedPurchaseProcessRequestException();
            }

            // If the flow continues it means the key doesn't exist, therefore we store the session
            $this->redisRepository->storePurchaseStatus($sessionId, Processing::name());
        } catch (DuplicatedPurchaseProcessRequestException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            // Log and continue the flow. An alarm will be set for this error so that we investigate.
            Log::error(
                'RedisPurchaseStatus Unexpected error occurred.',
                [
                    'message'   => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                ]
            );
        }
    }

    /**
     * @param string $sessionId Session Id
     *
     * @throws \ProBillerNG\Logger\Exception
     */
    public function removeKeyOfFinishedProcess(string $sessionId)
    {
        try {
            $this->redisRepository->deletePurchaseStatus($sessionId);

            Log::info('RedisPurchaseStatus Finish checking Redis for request already processing.');
        } catch (\Exception $exception) {
            // Log and continue the flow. An alarm will be set for this error so that we investigate.
            Log::error(
                'RedisPurchaseStatus Unexpected error occurred.',
                [
                    'message'   => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                ]
            );
        }
    }
}