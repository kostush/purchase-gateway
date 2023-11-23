<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedger;
use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedgerRepository;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\SiteServiceStatus;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly as SiteRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;
use Tests\UnitTestCase;

class SiteServiceStatusTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_a_valid_instance_of_service(): void
    {
        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $siteRepoMock = $this->createMock(SiteRepository::class);

        $service = new SiteServiceStatus(
            $projectionPositionLedgerRepoMock,
            $siteRepoMock
        );

        $this->assertInstanceOf(SiteServiceStatus::class, $service);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_no_ledger_found(): void
    {
        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );
        $projectionPositionLedgerRepoMock->method('findByName')->willReturn(null);

        $siteRepoMock = $this->createMock(SiteRepository::class);

        $service = new SiteServiceStatus(
            $projectionPositionLedgerRepoMock,
            $siteRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_ledger_found_and_interval_bigger_than_four_days(): void
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable('- 5 Days'));

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $siteRepoMock = $this->createMock(SiteRepository::class);

        $service = new SiteServiceStatus(
            $projectionPositionLedgerRepoMock,
            $siteRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_ledger_found_and_interval_smaller_than_four_days_and_no_sites(): void
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable());

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $siteRepoMock = $this->createMock(DoctrineSiteProjectionRepository::class);
        $siteRepoMock->method('countAll')->willReturn(0);

        $service = new SiteServiceStatus(
            $projectionPositionLedgerRepoMock,
            $siteRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_true_when_ledger_found_and_interval_smaller_than_four_days_and_sites_more_than_one(): void
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable());

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $siteRepoMock = $this->createMock(DoctrineSiteProjectionRepository::class);
        $siteRepoMock->method('countAll')->willReturn(1);

        $service = new SiteServiceStatus(
            $projectionPositionLedgerRepoMock,
            $siteRepoMock
        );

        $this->assertTrue($service->ledgerStatus('service-name'));
    }
}
