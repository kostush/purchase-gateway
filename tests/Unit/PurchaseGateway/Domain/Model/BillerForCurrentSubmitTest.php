<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerForCurrentSubmit;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use Tests\UnitTestCase;

class BillerForCurrentSubmitTest extends UnitTestCase
{
    /**
     * @var Cascade
     */
    private $cascade;

    /**
     * @var PaymentTemplate
     */
    private $paymentTemplate;

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function setUp(): void
    {
        $this->cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller()
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS - 1,
            0
        );

        $this->paymentTemplate = PaymentTemplate::create(
            'a8844715-815d-43c0-9eab-53d9bcf61dbc',
            '123456',
            '4321',
            '2024',
            '12',
            '2020-06-12 12:10:20',
            '2020-06-12 12:10:20',
            'rocketgate',
            []
        );

        parent::setUp();
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     */
    public function it_should_return_the_same_biller_when_it_has_submits_left(): void
    {
        $billerForCurrentSubmit = BillerForCurrentSubmit::create(
            $this->cascade,
            null
        );

        $this->assertInstanceOf(RocketgateBiller::class, $billerForCurrentSubmit->biller());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_the_next_biller_from_cascade_when_no_more_submits_left(): void
    {
        $this->cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller()
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS,
            0
        );

        $billerForCurrentSubmit = BillerForCurrentSubmit::create(
            $this->cascade,
            null
        );

        $this->assertInstanceOf(NetbillingBiller::class, $billerForCurrentSubmit->biller());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidNextBillerException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_the_biller_from_payment_template_when_it_is_used(): void
    {
        $this->cascade = Cascade::create(
            BillerCollection::buildBillerCollection(
                [
                    new RocketgateBiller(),
                    new NetbillingBiller()
                ]
            ),
            new RocketgateBiller(),
            RocketgateBiller::MAX_SUBMITS,
            0
        );

        $billerForCurrentSubmit = BillerForCurrentSubmit::create(
            $this->cascade,
            $this->paymentTemplate
        );

        $this->assertInstanceOf(RocketgateBiller::class, $billerForCurrentSubmit->biller());
    }
}
