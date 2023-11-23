<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;
use Tests\UnitTestCase;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;

class ChequePaymentInfoTest extends UnitTestCase
{
    public const ROUTING_NUMBER        = "999999999";

    public const ACCOUNT_NUMBER        = "112233";

    public const SAVING_ACCOUNT        = false; // boolean defaults to false

    public const SOCIAL_SECURITY_LAST4 = "5233"; // last 4 digits

    /**
     * @test
     * @return ChequePaymentInfo
     *
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_cheque_payment_info_object(): ChequePaymentInfo
    {
        $chequePaymentInfo = ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            ChequePaymentInfo::PAYMENT_TYPE,
            ChequePaymentInfo::PAYMENT_METHOD
        );

        if ($chequePaymentInfo instanceof ChequePaymentInfo) {
            $this->assertInstanceOf(ChequePaymentInfo::class, $chequePaymentInfo);
        }

        return $chequePaymentInfo;
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_routing_number(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(self::ROUTING_NUMBER, $chequePaymentInfo->routingNumber());
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_account_number(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(self::ACCOUNT_NUMBER, $chequePaymentInfo->accountNumber());
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_saving_account(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(self::SAVING_ACCOUNT, $chequePaymentInfo->savingAccount());
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_social_security_last_4(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(self::SOCIAL_SECURITY_LAST4, $chequePaymentInfo->socialSecurityLast4());
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_payment_method(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(ChequePaymentInfo::PAYMENT_METHOD, $chequePaymentInfo->paymentMethod());
    }

    /**
     * @test
     *
     * @param ChequePaymentInfo $chequePaymentInfo
     *
     * @depends it_should_return_cheque_payment_info_object
     *
     * @return void
     */
    public function it_should_have_the_correct_payment_type(ChequePaymentInfo $chequePaymentInfo): void
    {
        $this->assertSame(ChequePaymentInfo::PAYMENT_TYPE, $chequePaymentInfo->paymentType());
    }

    /**
     * @test
     * @return void
     *
     * @throws InvalidPaymentInfoException
     */
    public function it_should_throw_invalid_payment_info_exception_for_empty_routing_number(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        ChequePaymentInfo::create(
            '',
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            ChequePaymentInfo::PAYMENT_TYPE,
            ChequePaymentInfo::PAYMENT_METHOD
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_invalid_payment_info_exception_for_empty_account_number(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            '',
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            ChequePaymentInfo::PAYMENT_TYPE,
            ChequePaymentInfo::PAYMENT_METHOD
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_invalid_payment_info_exception_for_empty_social_security_last4(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            '',
            ChequePaymentInfo::PAYMENT_TYPE,
            ChequePaymentInfo::PAYMENT_METHOD
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_unsupported_payment_method_exception_for_unsupported_payment_method(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            ChequePaymentInfo::PAYMENT_TYPE,
            'unsupportedPaymentMethod'
        );
    }

    /**
     * @test
     * @return void
     *
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_invalid_payment_method_exception_for_empty_payment_method(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            ChequePaymentInfo::PAYMENT_TYPE,
            ''
        );
    }


    /**
     * @test
     * @return void
     *
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws Exception
     */
    public function it_should_throw_unsupported_payment_type_exception_for_empty_payment_type(): void
    {
        $this->expectException(UnsupportedPaymentTypeException::class);

        ChequePaymentInfo::create(
            self::ROUTING_NUMBER,
            self::ACCOUNT_NUMBER,
            self::SAVING_ACCOUNT,
            self::SOCIAL_SECURITY_LAST4,
            '',
            ChequePaymentInfo::PAYMENT_METHOD
        );
    }

    /**
     * @test
     * @dataProvider accountNumberWithObsfuscatedResults
     * @param array $accountNumberWithResult
     */
    public function it_should_obfuscate_account_number(array $accountNumberWithResult): void
    {
        $obfuscatedAccounNumber = ChequePaymentInfo::obfuscateAccountNumber($accountNumberWithResult['accountNumber']);
        $this->assertEquals($accountNumberWithResult['expectedResult'], $obfuscatedAccounNumber);
    }

    /**
     * @return array
     */
    public function accountNumberWithObsfuscatedResults(): array
    {
        return [
            [['accountNumber' => '', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING]],
            [['accountNumber' => '123', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '123']],
            [['accountNumber' => '1234', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '1234']],
            [['accountNumber' => '12345', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '2345']],
            [['accountNumber' => '12345678', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '5678']],
            [['accountNumber' => '123456789', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '6789']],
            [['accountNumber' => '3211752611234567', 'expectedResult' => ObfuscatedData::OBFUSCATED_STRING . '4567']],
        ];
    }
}
