<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Mgpg;

use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionExpiredRenew;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionTrialUpgrade;
use ProbillerMGPG\SubsequentOperations\Init\Operation\SubscriptionUpgrade;
use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\BusinessTransactionOperationNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBusinessTransactionOperationException;

class SubsequentOperationFactory
{
    /**
     * @param string $businessTransactionOperation
     * @return string
     * @throws Exception
     * @throws BusinessTransactionOperationNotFoundException
     * @throws InvalidBusinessTransactionOperationException
     */
    public static function createSubsequentOperation(string $businessTransactionOperation): string
    {
        try {
            switch (BusinessTransactionOperation::value($businessTransactionOperation)) {
                case BusinessTransactionOperation::SUBSCRIPTIONUPGRADE:
                    Log::info('CreateSubsequentOperation Class used: ' . SubscriptionUpgrade::class);
                    return SubscriptionUpgrade::class;
                case BusinessTransactionOperation::SUBSCRIPTIONTRIALUPGRADE:
                    Log::info('CreateSubsequentOperation Class used: ' . SubscriptionTrialUpgrade::class);
                    return SubscriptionTrialUpgrade::class;
                case BusinessTransactionOperation::SUBSCRIPTIONEXPIREDRENEW:
                    Log::info('CreateSubsequentOperation Class used: ' . SubscriptionExpiredRenew::class);
                    return SubscriptionExpiredRenew::class;
                default:
                    throw new InvalidBusinessTransactionOperationException($businessTransactionOperation);
            }
        } catch (\UnexpectedValueException $e) {
            throw new BusinessTransactionOperationNotFoundException($businessTransactionOperation);
        }
    }
}
