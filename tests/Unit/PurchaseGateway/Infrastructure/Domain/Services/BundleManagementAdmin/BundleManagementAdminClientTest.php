<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin;

use ProbillerNG\BundleManagementAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\BundleManagementAdminServiceClient\ApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\BundleManagementAdminClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BundleManagementAdmin\Exceptions\BundleManagementAdminApiException;

use Tests\UnitTestCase;

class BundleManagementAdminClientTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws BundleManagementAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_when_api_error(): void
    {
        $this->expectException(BundleManagementAdminApiException::class);

        $domainEventsApiMock = $this->createMock(DomainEventsApi::class);
        $domainEventsApiMock->method('retrieveDomainEvents')->willThrowException(new ApiException());

        $bundleManagementAdminClient = new BundleManagementAdminClient($domainEventsApiMock);

        $bundleManagementAdminClient->retrieveDomainEvents(0, 10);
    }

    /**
     * @test
     * @return void
     * @throws BundleManagementAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_retrieve_domain_events_once(): void
    {
        $domainEventsApiMock = $this->createMock(DomainEventsApi::class);
        $domainEventsApiMock->expects($this->once())->method('retrieveDomainEvents');

        $bundleManagementAdminClient = new BundleManagementAdminClient($domainEventsApiMock);

        $bundleManagementAdminClient->retrieveDomainEvents(0, 10);
    }
}
