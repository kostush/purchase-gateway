<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseGatewayHealth;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedger;
use ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedgerRepository;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseGatewayHealth\BundleServiceStatus;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly as BundleRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use Tests\UnitTestCase;

class BundleServiceStatusTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_a_valid_instance_of_service()
    {
        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $bundleRepoMock = $this->createMock(BundleRepository::class);

        $service = new BundleServiceStatus(
            $projectionPositionLedgerRepoMock,
            $bundleRepoMock
        );

        $this->assertInstanceOf(BundleServiceStatus::class, $service);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_no_ledger_found()
    {
        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );
        $projectionPositionLedgerRepoMock->method('findByName')->willReturn(null);

        $bundleRepoMock = $this->createMock(BundleRepository::class);

        $service = new BundleServiceStatus(
            $projectionPositionLedgerRepoMock,
            $bundleRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_ledger_found_and_interval_bigger_than_four_days()
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable('- 5 Days'));

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $bundleRepoMock = $this->createMock(BundleRepository::class);

        $service = new BundleServiceStatus(
            $projectionPositionLedgerRepoMock,
            $bundleRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_false_when_ledger_found_and_interval_smaller_than_four_days_and_no_bundles()
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable());

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $bundleRepoMock = $this->createMock(DoctrineBundleProjectionRepository::class);
        $bundleRepoMock->method('count')->willReturn(0);

        $service = new BundleServiceStatus(
            $projectionPositionLedgerRepoMock,
            $bundleRepoMock
        );

        $this->assertFalse($service->ledgerStatus('service-name'));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_true_when_ledger_found_and_interval_smaller_than_four_days_and_bundles_more_than_one()
    {
        $projectionPositionLedgerMock = $this->createMock(ProjectionPositionLedger::class);
        $projectionPositionLedgerMock->method('lastModified')->willReturn(new \DateTimeImmutable());

        $projectionPositionLedgerRepoMock = $this->createMock(
            ProjectionPositionLedgerRepository::class
        );

        $projectionPositionLedgerRepoMock->method('findByName')->willReturn($projectionPositionLedgerMock);

        $bundleRepoMock = $this->createMock(DoctrineBundleProjectionRepository::class);
        $bundleRepoMock->method('count')->willReturn(1);

        $service = new BundleServiceStatus(
            $projectionPositionLedgerRepoMock,
            $bundleRepoMock
        );

        $this->assertTrue($service->ledgerStatus('service-name'));
    }
}
