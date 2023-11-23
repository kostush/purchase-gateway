<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminApiException;
use ProbillerNG\SiteAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\SiteAdminServiceClient\ApiException;
use ProbillerNG\SiteAdminServiceClient\Model\Error;
use ProbillerNG\SiteAdminServiceClient\Model\InlineResponse2004;

class SiteAdminClient extends ServiceClient
{
    /**
     * @var DomainEventsApi
     */
    private $eventsApi;

    /**
     * SiteAdminClient constructor.
     * @param DomainEventsApi $eventsApi DomainEventsApi
     * @return void
     */
    public function __construct(DomainEventsApi $eventsApi)
    {
        $this->eventsApi = $eventsApi;
    }

    /**
     * @param int|null $lastProjectedItemId Last Projected Item Id
     * @param int      $batchSize           Batch Size
     * @return Error|InlineResponse2004
     * @throws SiteAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveDomainEvents(?int $lastProjectedItemId, int $batchSize)
    {
        try {
            return $this->eventsApi->retrieveDomainEvents(
                'siteMapping',
                $lastProjectedItemId,
                null,
                $batchSize
            );
        } catch (ApiException $exception) {
            throw new SiteAdminApiException($exception, $exception->getMessage(), $exception->getCode());
        }
    }
}
