<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ExpiredCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use Tests\UnitTestCase;

class NewCCPaymentInfoTest extends UnitTestCase
{
    /**
     * @test
     * @return NewCCPaymentInfo
     * @throws Exception
     * @throws InvalidCreditCardExpirationDate
     * @throws \Throwable
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): NewCCPaymentInfo
    {
        $newCCPaymentInfo = NewCCPaymentInfo::create(
            '1234123412341234',
            '888',
            '09',
            '2099',
            null
        );
        $this->assertInstanceOf(NewCCPaymentInfo::class, $newCCPaymentInfo);

        return $newCCPaymentInfo;
    }

    /**
     * @test
     * @param NewCCPaymentInfo $newCCPaymentInfo NewCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_cc_number_method_is_called(
        NewCCPaymentInfo $newCCPaymentInfo
    ): void {
        $this->assertEquals('1234123412341234', $newCCPaymentInfo->ccNumber());
    }

    /**
     * @test
     * @param NewCCPaymentInfo $newCCPaymentInfo NewCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_cvv_method_is_called(NewCCPaymentInfo $newCCPaymentInfo): void
    {
        $this->assertEquals('888', $newCCPaymentInfo->cvv());
    }

    /**
     * @test
     * @param NewCCPaymentInfo $newCCPaymentInfo NewCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_expiration_month_method_is_called(
        NewCCPaymentInfo $newCCPaymentInfo
    ): void {
        $this->assertEquals('09', $newCCPaymentInfo->expirationMonth());
    }

    /**
     * @test
     * @param NewCCPaymentInfo $newCCPaymentInfo NewCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_expiration_year_method_is_called(
        NewCCPaymentInfo $newCCPaymentInfo
    ): void {
        $this->assertEquals('2099', $newCCPaymentInfo->expirationYear());
    }

    /**
     * @test
     * @param NewCCPaymentInfo $newCCPaymentInfo NewCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_payment_method_method_is_called(
        NewCCPaymentInfo $newCCPaymentInfo
    ): void {
        $this->assertEquals('cc', $newCCPaymentInfo->paymentType());
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_expired_credit_card_expiration_date_exception_when_expired_date_is_provided(): void
    {
        $this->expectException(ExpiredCreditCardExpirationDate::class);

        NewCCPaymentInfo::create(
            '1234123412341234',
            '888',
            '01',
            '2019',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_ccnumber_provided()
    {
        $this->expectException(InvalidPaymentInfoException::class);

        NewCCPaymentInfo::create(
            '',
            '888',
            '01',
            '2019',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_cc_provided()
    {
        $this->expectException(InvalidPaymentInfoException::class);

        NewCCPaymentInfo::create(
            '1234123412341234',
            '',
            '01',
            '2019',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_expiration_month_provided()
    {
        $this->expectException(InvalidPaymentInfoException::class);

        NewCCPaymentInfo::create(
            '1234123412341234',
            '888',
            '',
            '2019',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws InvalidCreditCardExpirationDate
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_expiration_year_provided()
    {
        $this->expectException(InvalidPaymentInfoException::class);

        NewCCPaymentInfo::create(
            '1234123412341234',
            '888',
            '01',
            '',
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_invalid_expiration_date_provided()
    {
        $this->expectException(InvalidCreditCardExpirationDate::class);

        NewCCPaymentInfo::create(
            '1234123412341234',
            '888',
            '48',
            '2423023',
            null
        );
    }
}
