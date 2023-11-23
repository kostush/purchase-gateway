<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;

class RetrieveBundleManagementEventsCommand extends ExternalCommand
{
    /**
     * @var RetrieveBundleManagementEventsAdapter
     */
    private $adapter;

    /**
     * @var int|null
     */
    private $lastProjectedItemId;

    /**
     * @var int
     */
    private $batchSize;

    /**
     * RetrieveBundleManagementEventsCommand constructor.
     * @param RetrieveBundleManagementEventsAdapter $adapter             Retrieve Bundle Management Events Adapter
     * @param int|null                              $lastProjectedItemId Last Projected Item Id
     * @param int                                   $batchSize           Batch Size
     */
    public function __construct(
        RetrieveBundleManagementEventsAdapter $adapter,
        ?int $lastProjectedItemId,
        int $batchSize
    ) {
        $this->adapter             = $adapter;
        $this->lastProjectedItemId = $lastProjectedItemId;
        $this->batchSize           = $batchSize;
    }

    /**
     * @return array
     * @throws Exceptions\BundleManagementAdminApiException
     * @throws Exceptions\BundleManagementAdminCodeErrorException
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function run(): array
    {
        return $this->adapter->retrieveEvents(
            $this->lastProjectedItemId,
            $this->batchSize
        );
    }

    /**
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): array
    {
        $exception = $this->getExecutionException();
        if ($exception instanceof \Throwable) {
            Log::logException($exception);
        }

        return [];
    }
}
