<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\DTO\PurchaseGatewayHealth\HttpQueryHealthDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\BundleServiceStatus;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\SiteServiceStatus;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\RetrievePurchaseGatewayHealthQuery;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\RetrievePurchaseGatewayHealthQueryHandler;
use ProBillerNG\PurchaseGateway\Domain\Services\ServiceStatusVerifier;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\PostbackJobsRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;
use Tests\UnitTestCase;

class RetrievePurchaseGatewayHealthQueryHandlerTest extends UnitTestCase
{
    /** @var MockObject */
    private $siteRepositoryMock;

    /** @var MockObject */
    private $repoMock;

    /** @var MockObject */
    private $queryMock;

    /** @var MockObject */
    private $circuitBreakerServiceMock;

    /** @var MockObject|BundleServiceStatus */
    private $bundleServiceStatus;

    /** @var MockObject|SiteServiceStatus */
    private $siteServiceStatus;

    /**
     * Regular setup
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->siteRepositoryMock = $this->getMockBuilder(DoctrineSiteProjectionRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['countAll'])->getMock();

        $this->repoMock                  = $this->createMock(PostbackJobsRepository::class);
        $this->queryMock                 = $this->createMock(RetrievePurchaseGatewayHealthQuery::class);
        $this->circuitBreakerServiceMock = $this->createMock(ServiceStatusVerifier::class);
        $this->bundleServiceStatus       = $this->createMock(BundleServiceStatus::class);
        $this->siteServiceStatus         = $this->createMock(SiteServiceStatus::class);
    }

    /**
     * @test
     * @return void
     * @throws \DomainException
     * @throws \Exception
     */
    public function returned_purchase_gateway_health_should_contain_ok_status_in_case_number_of_site_configurations_more_than_zero(): void
    {
        $this->siteRepositoryMock->method('countAll')->willReturn(1);

        $assembler = new HttpQueryHealthDTOAssembler();

        $handler = new RetrievePurchaseGatewayHealthQueryHandler(
            $this->siteRepositoryMock,
            $assembler,
            $this->repoMock,
            $this->circuitBreakerServiceMock,
            $this->bundleServiceStatus,
            $this->siteServiceStatus
        );

        $response = json_decode($handler->execute($this->queryMock));
        $this->assertEquals(RetrievePurchaseGatewayHealthQueryHandler::HEALTH_OK, $response->status);
    }

    /**
     * @test
     * @return void
     * @throws \DomainException
     * @throws \Exception
     */
    public function returned_purchase_gateway_health_should_contain_down_status_in_case_number_of_site_configurations_equals_zero(): void
    {
        $this->siteRepositoryMock->method('countAll')->willReturn(0);

        $assembler = new HttpQueryHealthDTOAssembler();

        $handler = new RetrievePurchaseGatewayHealthQueryHandler(
            $this->siteRepositoryMock,
            $assembler,
            $this->repoMock,
            $this->circuitBreakerServiceMock,
            $this->bundleServiceStatus,
            $this->siteServiceStatus
        );

        $response = json_decode($handler->execute($this->queryMock));
        $this->assertEquals(RetrievePurchaseGatewayHealthQueryHandler::HEALTH_DOWN, $response->status);
    }

    /**
     * @test
     * @return void
     * @throws \DomainException
     * @throws \Exception
     */
    public function returned_purchase_gateway_health_should_contain_down_status_in_case_of_exception_is_thrown_by_handler(): void
    {
        $this->siteRepositoryMock->method('countAll')->will($this->throwException(new \Exception()));

        $assembler = new HttpQueryHealthDTOAssembler();

        $handler = new RetrievePurchaseGatewayHealthQueryHandler(
            $this->siteRepositoryMock,
            $assembler,
            $this->repoMock,
            $this->circuitBreakerServiceMock,
            $this->bundleServiceStatus,
            $this->siteServiceStatus
        );

        $response = json_decode($handler->execute($this->queryMock));
        $this->assertEquals(RetrievePurchaseGatewayHealthQueryHandler::HEALTH_DOWN, $response->status);
    }
}
