<?php

namespace PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use Tests\UnitTestCase;

class QyssoBillerTest extends UnitTestCase
{

    /**
     * @test
     * @return QyssoBiller
     */
    public function it_should_create_correct_biller():QyssoBiller
    {
        $biller = new QyssoBiller();

        self::assertInstanceOf(QyssoBiller::class, $biller);

        return $biller;
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_contain_the_correct_biller_name(QyssoBiller $biller): void
    {
        self::assertSame(QyssoBiller::BILLER_NAME, $biller->name());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_contain_the_correct_biller_name_if_requested_as_string(QyssoBiller $biller): void
    {
        self::assertSame(QyssoBiller::BILLER_NAME, (string) $biller);
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_contain_the_correct_biller_id(QyssoBiller $biller): void
    {
        self::assertSame(QyssoBiller::BILLER_ID, $biller->id());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_return_true_for_third_party(QyssoBiller $biller): void
    {
        self::assertTrue($biller->isThirdParty());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_return_false_for_three_d_support(QyssoBiller $biller): void
    {
        self::assertFalse($biller->isThreeDSupported());
    }

    /**
     * @test
     * @depends it_should_create_correct_biller
     * @param QyssoBiller $biller Qysso biller
     */
    public function it_should_allow_add_payment_method(QyssoBiller $biller): void
    {
        $biller->addPaymentMethod('paymentMethod1');

        self::assertContains('paymentMethod1', $biller->availablePaymentMethods());
    }
}
