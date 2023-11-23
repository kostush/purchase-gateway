<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NoBillersInCascadeException;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\RemovedBillerCollectionForThreeDS;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use Tests\UnitTestCase;

class CascadeTest extends UnitTestCase
{
    /**
     * @test
     * @return array
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_an_array_when_to_array_method_is_called(): array
    {
        $cascade = Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]));
        $cascade = $cascade->toArray();

        $this->assertIsArray($cascade);

        return $cascade;
    }

    /**
     * @test
     * @param array $cascade Cascade
     * @depends it_should_return_an_array_when_to_array_method_is_called
     * @return void
     */
    public function it_should_have_a_biller_collection(array $cascade): void
    {
        $this->assertIsArray($cascade['billers']);
    }

    /**
     * @test
     * @param array $cascade Cascade
     * @depends it_should_return_an_array_when_to_array_method_is_called
     * @return void
     */
    public function it_should_have_a_current_biller(array $cascade): void
    {
        $this->assertArrayHasKey('currentBiller', $cascade);
    }

    /**
     * @test
     * @param array $cascade Cascade
     * @depends it_should_return_an_array_when_to_array_method_is_called
     * @return void
     */
    public function it_should_have_a_current_biller_submit(array $cascade): void
    {
        $this->assertArrayHasKey('currentBillerSubmit', $cascade);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_have_rocketgate_as_default_biller(): void
    {
        $this->assertSame('rocketgate', (string) Cascade::defaultBiller());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws InvalidNextBillerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_rocketgate_as_next_biller_when_creating_a_cascade_with_default_current_position(
    ): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            )
        );

        $nextBiller = $cascade->nextBiller();

        $this->assertSame(RocketgateBiller::BILLER_NAME, $nextBiller->name());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws InvalidNextBillerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_netbilling_as_next_biller_when_creating_a_cascade_with_current_position_set_to_last(
    ): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS,
            0,
            null
        );

        $nextBiller = $cascade->nextBiller();

        $this->assertSame(NetbillingBiller::BILLER_NAME, $nextBiller->name());
    }

    /**
     * @test
     * @return void
     * @throws InvalidNextBillerException
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_throw_exception_when_creating_a_cascade_with_current_biller_last(): void
    {
        $this->expectException(InvalidNextBillerException::class);

        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new NetbillingBiller(),
            NetbillingBiller::MAX_SUBMITS,
            1,
            null
        );

        $cascade->nextBiller();
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_false_if_next_biller_is_not_third_party(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS,
            1,
            null
        );

        $this->assertFalse($cascade->isTheNextBillerThirdParty());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_false_if_next_biller_does_not_exist(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS
        );

        $this->assertFalse($cascade->isTheNextBillerThirdParty());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException|NoBillersInCascadeException
     */
    public function it_should_remove_non_threeDS_biller_from_cascade(): void
    {
        $this->expectException(NoBillersInCascadeException::class);

        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new NetbillingBiller()
                ]
            )
        );

        $cascade->removeNonThreeDSBillers();
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_true_if_next_biller_is_third_party(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new EpochBiller(),
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS
        );

        $this->assertTrue($cascade->isTheNextBillerThirdParty());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException|NoBillersInCascadeException
     */
    public function it_should_remove_epoch_biller_from_cascade(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new EpochBiller(),
                    new RocketgateBiller()

                ]
            )
        );

        $cascade->removeEpochBiller();

        $this->assertEquals(1, $cascade->billers()->count());
        $this->assertEquals(RocketgateBiller::BILLER_NAME, $cascade->firstBiller()->name());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_two_billers_if_remove_non_three_ds_billers_method_is_called_and_the_current_biller_submit_bigger_than_zero(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new RocketgateBiller(),
            1
        );

        $cascade->removeNonThreeDSBillers();

        $this->assertSame(2, $cascade->billers()->count());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_only_rocketgate_if_remove_non_three_ds_billers_method_is_called(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new RocketgateBiller()
        );

        $cascade->removeNonThreeDSBillers();

        $this->assertSame(1, $cascade->billers()->count());
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     * @throws \Exception
     */
    public function it_should_return_removed_netbilling_for_three_DS_as_true_when_remove_non_three_ds_billers_method_is_called(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new NetbillingBiller(),
                    new RocketgateBiller()
                ]
            ),
            new NetbillingBiller(),
            0,
            0,
            null
        );

        $cascade->removeNonThreeDSBillers();

        $this->assertSame(true, $cascade->removedBillersFor3DS()->contains(NetbillingBiller::BILLER_NAME));
    }

    /**
     * @test
     * @return void
     * @throws LoggerException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_removed_netbilling_for_three_DS_as_false_when_remove_non_three_ds_billers_method_is_not_called(): void
    {
        $cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller(),
                ]
            ),
            new RocketgateBiller()
        );

        $this->assertInstanceOf(RemovedBillerCollectionForThreeDS::class, $cascade->removedBillersFor3DS());
    }
}
