<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\RepositoryConnectionException;
use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Exception\TransientConfigServiceException;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Classification;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Permanent;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Transient;
use ProBillerNG\ServiceBus\ExceptionErrorClassification\Unknown;
use ProBillerNG\ServiceBus\ExceptionErrorClassifier;

class PurchaseGatewayErrorClassifier implements ExceptionErrorClassifier
{
    /**
     * @param \Exception $exception exception
     * @return Classification
     * @throws \ProBillerNG\Logger\Exception
     */
    public function classify(\Exception $exception): Classification
    {
        $classification = new Unknown(Classification::ABORT);

        if ($exception instanceof RepositoryConnectionException) {
            Log::info('Connection error. Cannot connect to repository: will try again later.');
            return new Transient(Classification::ABORT);
        }

        if ($exception instanceof TransientConfigServiceException) {
            Log::info(
                'Communication error with config-service: will try again later.',
                ['message' => $exception->getMessage()]
            );

            return new Transient(Classification::ABORT);
        }

        if ($exception instanceof Exception) {
            Log::info('Purchase gateway encountered a permanent exception');
            return new Permanent(Classification::ABORT);
        }

        if ($classification instanceof Unknown) {
            Log::error('Unknown exception encountered during message handling');
            Log::logException($exception);
        }

        return $classification;
    }
}
