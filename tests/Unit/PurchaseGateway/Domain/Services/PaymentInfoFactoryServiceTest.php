<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingOtherPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentInfoFactoryService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use Tests\UnitTestCase;

class PaymentInfoFactoryServiceTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     */
    public function it_should_return_cc_payment_info_when_cc_payment_type_is_given(): void
    {
        $this->assertInstanceOf(
            CCPaymentInfo::class,
            PaymentInfoFactoryService::create(CCPaymentInfo::PAYMENT_TYPE, null)
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     */
    public function it_should_return_other_payment_info_when_other_than_cc_payment_type_is_given(): void
    {
        $this->assertInstanceOf(
            OtherPaymentTypeInfo::class,
            PaymentInfoFactoryService::create(OtherPaymentTypeInfo::PAYMENT_TYPES[0], null)
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     */
    public function it_should_return_existing_cc_payment_info_when_card_hash_is_given(): void
    {
        $this->assertInstanceOf(
            ExistingCCPaymentInfo::class,
            PaymentInfoFactoryService::create(
                CCPaymentInfo::PAYMENT_TYPE,
                'visa',
                $this->faker->uuid,
                $this->faker->uuid
            )
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws InvalidPaymentInfoException
     */
    public function it_should_return_existing_other_payment_info_when_payment_template_id_given(): void
    {
        $this->assertInstanceOf(
            ExistingOtherPaymentInfo::class,
            PaymentInfoFactoryService::create(
                OtherPaymentTypeInfo::PAYMENT_TYPES[0],
                null,
                null,
                $this->faker->uuid
            )
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_cc_payment_info_when_payment_method_is_valid(): void
    {
        self::assertInstanceOf(
            CCPaymentInfo::class,
            PaymentInfoFactoryService::create(CCPaymentInfo::PAYMENT_TYPE, PaymentInfo::PAYMENT_METHODS[0])
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_other_payment_info_when_payment_method_is_valid(): void
    {
        self::assertInstanceOf(
            OtherPaymentTypeInfo::class,
            PaymentInfoFactoryService::create(OtherPaymentTypeInfo::PAYMENT_TYPES[0], PaymentInfo::PAYMENT_METHODS[0])
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_existing_cc_when_hash_is_given_and_payment_method_valid(): void
    {
        self::assertInstanceOf(
            ExistingCCPaymentInfo::class,
            PaymentInfoFactoryService::create(
                CCPaymentInfo::PAYMENT_TYPE,
                PaymentInfo::PAYMENT_METHODS[0],
                $this->faker->uuid
            )
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_existing_other_when_template_is_given_and_payment_method_valid(): void
    {
        self::assertInstanceOf(
            ExistingOtherPaymentInfo::class,
            PaymentInfoFactoryService::create(
                OtherPaymentTypeInfo::PAYMENT_TYPES[0],
                PaymentInfo::PAYMENT_METHODS[0],
                null,
                $this->faker->uuid
            )
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_an_error_for_existing_cc_when_hash_is_given_and_payment_method_invalid(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        PaymentInfoFactoryService::create(
            CCPaymentInfo::PAYMENT_TYPE,
            'invalidMethod',
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_an_error_for_existing_other_when_template_is_given_and_payment_method_invalid(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        PaymentInfoFactoryService::create(
            OtherPaymentTypeInfo::PAYMENT_TYPES[0],
            'invalidMethod',
            null,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_an_error_during_cc_payment_when_payment_method_is_invalid(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        PaymentInfoFactoryService::create(CCPaymentInfo::PAYMENT_TYPE, 'invalidMethod');
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidPaymentInfoException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_throw_an_error_during_other_payment_when_payment_method_is_invalid(): void
    {
        $this->expectException(UnsupportedPaymentMethodException::class);

        PaymentInfoFactoryService::create(OtherPaymentTypeInfo::PAYMENT_TYPES[0], 'invalidMethod');
    }
}
