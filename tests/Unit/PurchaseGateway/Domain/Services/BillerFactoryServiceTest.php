<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidForceCascadeException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerIdException;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use Tests\UnitTestCase;

class BillerFactoryServiceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_rocketgate_biller_object_when_rocketgate_biller_name_is_given(): void
    {
        $this->assertInstanceOf(
            RocketgateBiller::class,
            BillerFactoryService::create(RocketgateBiller::BILLER_NAME)
        );
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_unknown_biller_name_exception(): void
    {
        $this->expectException(UnknownBillerNameException::class);

        BillerFactoryService::create('unknownBillerName');
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_netbilling_biller_object_when_netbilling_biller_name_is_given(): void
    {
        $this->assertInstanceOf(
            NetbillingBiller::class,
            BillerFactoryService::create(NetbillingBiller::BILLER_NAME)
        );
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerNameException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_epoch_biller_object_when_epoch_biller_name_is_given(): void
    {
        $this->assertInstanceOf(
            EpochBiller::class,
            BillerFactoryService::create(EpochBiller::BILLER_NAME)
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_rocketgate_biller_object_when_test_rocketgate_force_cascade_is_given(): void
    {
        $this->assertInstanceOf(
            RocketgateBiller::class,
            BillerFactoryService::createFromForceCascade(RetrieveCascadeTranslatingService::TEST_ROCKETGATE)
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_invalid_force_cascade_exception(): void
    {
        $this->expectException(InvalidForceCascadeException::class);

        BillerFactoryService::createFromForceCascade('unknownForceCascade');
    }

    /**
     * @test
     * @return void
     * @throws InvalidForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_netbilling_biller_object_when_test_netbilling_force_cascade_is_given(): void
    {
        $this->assertInstanceOf(
            NetbillingBiller::class,
            BillerFactoryService::createFromForceCascade(RetrieveCascadeTranslatingService::TEST_NETBILLING)
        );
    }

    /**
     * @test
     * @return void
     * @throws InvalidForceCascadeException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_epoch_biller_object_when_test_epoch_force_cascade_is_given(): void
    {
        $this->assertInstanceOf(
            EpochBiller::class,
            BillerFactoryService::createFromForceCascade(RetrieveCascadeTranslatingService::TEST_EPOCH)
        );
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerIdException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_rocketgate_biller_object_when_rocketgate_biller_id_is_given(): void
    {
        $this->assertInstanceOf(
            RocketgateBiller::class,
            BillerFactoryService::createFromBillerId(RocketgateBiller::BILLER_ID)
        );
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerIdException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_throw_unknown_biller_id_exception(): void
    {
        $this->expectException(UnknownBillerIdException::class);

        BillerFactoryService::createFromBillerId('1');
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerIdException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_netbilling_biller_object_when_netbilling_biller_id_is_given(): void
    {
        $this->assertInstanceOf(
            NetbillingBiller::class,
            BillerFactoryService::createFromBillerId(NetbillingBiller::BILLER_ID)
        );
    }

    /**
     * @test
     * @return void
     * @throws UnknownBillerIdException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_an_epoch_biller_object_when_epoch_biller_id_is_given(): void
    {
        $this->assertInstanceOf(
            EpochBiller::class,
            BillerFactoryService::createFromBillerId(EpochBiller::BILLER_ID)
        );
    }
}
