<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProbillerNG\BundleManagementAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\BundleManagementAdminServiceClient\ApiException;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProbillerNG\BundleManagementAdminServiceClient\Model as ClientModel;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\Exceptions\BundleManagementAdminApiException;

class BundleManagementAdminClient extends ServiceClient
{
    /**
     * @var DomainEventsApi
     */
    private $eventsApi;

    /**
     * BundleManagementAdminClient constructor.
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
     * @return ClientModel\Error|ClientModel\InlineResponse2001|ClientModel\InlineResponse4002
     * @throws BundleManagementAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function retrieveDomainEvents(?int $lastProjectedItemId, int $batchSize)
    {
        try {
            return $this->eventsApi->retrieveDomainEvents(
                'bundleManagementAdmin',
                $lastProjectedItemId,
                null,
                $batchSize
            );
        } catch (ApiException $exception) {
            throw new BundleManagementAdminApiException($exception, $exception->getMessage(), $exception->getCode());
        }
    }
}
