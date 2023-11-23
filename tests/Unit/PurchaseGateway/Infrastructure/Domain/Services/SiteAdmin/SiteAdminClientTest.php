<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\Exceptions\SiteAdminApiException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\SiteAdmin\SiteAdminClient;
use ProbillerNG\SiteAdminServiceClient\Api\DomainEventsApi;
use ProbillerNG\SiteAdminServiceClient\ApiException;
use Tests\UnitTestCase;

class SiteAdminClientTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws SiteAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_exception_when_api_error(): void
    {
        $this->expectException(SiteAdminApiException::class);

        $domainEventsApiMock = $this->createMock(DomainEventsApi::class);
        $domainEventsApiMock->method('retrieveDomainEvents')->willThrowException(new ApiException());

        $siteAdminClient = new SiteAdminClient($domainEventsApiMock);

        $siteAdminClient->retrieveDomainEvents(0, 10);
    }

    /**
     * @test
     * @return void
     * @throws SiteAdminApiException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_call_retrieve_domain_events_once(): void
    {
        $domainEventsApiMock = $this->createMock(DomainEventsApi::class);
        $domainEventsApiMock->expects($this->once())->method('retrieveDomainEvents');

        $siteAdminClient = new SiteAdminClient($domainEventsApiMock);

        $siteAdminClient->retrieveDomainEvents(0, 10);
    }
}
