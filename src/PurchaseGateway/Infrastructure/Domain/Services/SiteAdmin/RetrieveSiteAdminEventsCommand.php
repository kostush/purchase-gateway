<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminCodeErrorException;
use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;

class RetrieveSiteAdminEventsCommand extends ExternalCommand
{
    /**
     * @var RetrieveSiteAdminEventsAdapter
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
     * RetrieveSiteAdminEventsCommand constructor.
     * @param RetrieveSiteAdminEventsAdapter $adapter             Retrieve Site Admin Events Adapter
     * @param int|null                       $lastProjectedItemId Last Projected Item Id
     * @param int                            $batchSize           Batch Size
     */
    public function __construct(
        RetrieveSiteAdminEventsAdapter $adapter,
        ?int $lastProjectedItemId,
        int $batchSize
    ) {
        $this->adapter             = $adapter;
        $this->lastProjectedItemId = $lastProjectedItemId;
        $this->batchSize           = $batchSize;
    }

    /**
     * @return array
     * @throws Exceptions\SiteAdminApiException
     * @throws SiteAdminCodeErrorException
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
        Log::logException($exception);

        return [];
    }
}
