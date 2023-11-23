<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\BinRouting;

use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\NetbillingBinRoutingTranslatingService;
use Tests\UnitTestCase;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\RocketgateBinRoutingTranslatingService;

class BinRoutingServiceFactoryTest extends UnitTestCase
{
    /**
     * @var LaravelBinRoutingServiceFactory
     */
    protected $binRoutingServiceFactory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->binRoutingServiceFactory = new LaravelBinRoutingServiceFactory();
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException
     */
    public function it_should_return_rocketgate_bin_routing_translating_service(): void
    {
        $service = $this->binRoutingServiceFactory->get(RocketgateBiller::BILLER_NAME);

        $this->assertInstanceOf(RocketgateBinRoutingTranslatingService::class, $service);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException
     */
    public function it_should_return_netbilling_adapter(): void
    {
        $service = $this->binRoutingServiceFactory->get(NetbillingBiller::BILLER_NAME);

        $this->assertInstanceOf(NetbillingBinRoutingTranslatingService::class, $service);
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_error_when_unknown_biller_name_passed(): void
    {
        $this->expectException(UnknownBillerNameException::class);

        $this->binRoutingServiceFactory->get('Unknown');
    }
}
