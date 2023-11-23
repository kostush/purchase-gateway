<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\PaymentCC;
use Tests\UnitTestCase;

class PaymentCCTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_valid_expiryDate_when_valid_month_and_year_is_provided()
    {
        $paymentCC = PaymentCC::create('123456', '1234', '01', '2021');

        $this->assertInstanceOf(PaymentCC::class, $paymentCC);
        $this->assertNotNull($paymentCC->toArray()['expiryDate']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_null_expiryDate_when_invalid_month_is_provided()
    {
        $paymentCC = PaymentCC::create('123456', '1234', '13', '2021');

        $this->assertInstanceOf(PaymentCC::class, $paymentCC);
        $this->assertNull($paymentCC->toArray()['expiryDate']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_null_expiryDate_when_invalid_year_is_provided()
    {
        $paymentCC = PaymentCC::create('123456', '1234', '5', '202202');

        $this->assertInstanceOf(PaymentCC::class, $paymentCC);
        $this->assertNull($paymentCC->toArray()['expiryDate']);
    }
}
