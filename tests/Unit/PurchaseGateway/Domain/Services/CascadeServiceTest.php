<?php

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\ForceCascadeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use Tests\UnitTestCase;

class CascadeServiceTest extends UnitTestCase
{
    /**
     * @var CascadeTranslatingService
     */
    protected $translatingService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->translatingService = $this->createMock(RetrieveCascadeTranslatingService::class);
        $this->translatingService->method('retrieveCascadeForInitPurchase')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );
    }

    /**
     * @test
     * @return Cascade
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_a_cascade_when_retrieve_cascade_method_is_called(): Cascade
    {
        $cascadeService = new CascadeService($this->translatingService);
        $cascade        = $cascadeService->retrieveCascade(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->countryCode,
            'cc',
            'visa',
            'ALL',
            RetrieveCascadeTranslatingService::TEST_NETBILLING,
            null
        );

        $this->assertInstanceOf(Cascade::class, $cascade);

        return $cascade;
    }

    /**
     * @test
     * @depends it_should_return_a_cascade_when_retrieve_cascade_method_is_called
     * @param Cascade $cascade Cascade
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     */
    public function it_should_return_netbilling(Cascade $cascade): void
    {
        $this->assertEquals(new NetbillingBiller(), $cascade->nextBiller());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_rocketgate_when_forced_cascade_is_used(): void
    {
        $cascadeService = new CascadeService($this->translatingService);
        $cascade        = $cascadeService->retrieveCascade(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->countryCode,
            'cc',
            'visa',
            'ALL',
            RetrieveCascadeTranslatingService::TEST_ROCKETGATE,
            null
        );

        $this->assertEquals(new RocketgateBiller(), $cascade->nextBiller());
    }


    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_rocketgate_when_initial_biller_is_used(): void
    {
        $cascadeService = new CascadeService($this->translatingService);

        $cascade = $cascadeService->retrieveCascade(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->countryCode,
            'cc',
            'visa',
            'ALL',
            null,
            RocketgateBiller::BILLER_NAME
        );

        $this->assertEquals(new RocketgateBiller(), $cascade->nextBiller());
    }
}
