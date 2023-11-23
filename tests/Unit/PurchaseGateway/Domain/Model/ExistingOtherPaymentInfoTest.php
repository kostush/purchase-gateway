<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingOtherPaymentInfo;
use Tests\UnitTestCase;

class ExistingOtherPaymentInfoTest extends UnitTestCase
{
    /** @var string */
    protected $paymentTemplateId = 'some-uuid-asdi-asdasd-asd-asddd';
    /**
     * @test
     * @return ExistingOtherPaymentInfo
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): ExistingOtherPaymentInfo
    {
        $existingOtherPaymentInfo = ExistingOtherPaymentInfo::create(
            $this->paymentTemplateId,
            'ewallet',
            null
        );
        $this->assertInstanceOf(ExistingOtherPaymentInfo::class, $existingOtherPaymentInfo);

        return $existingOtherPaymentInfo;
    }

    /**
     * @test
     * @param ExistingOtherPaymentInfo $existingOtherPaymentInfo ExistingOtherPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_payment_template_method_is_called(
        ExistingOtherPaymentInfo $existingOtherPaymentInfo
    ): void {
        $this->assertEquals($this->paymentTemplateId, $existingOtherPaymentInfo->paymentTemplateId());
    }

    /**
     * @test
     * @param ExistingOtherPaymentInfo $existingOtherPaymentInfo ExistingOtherPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_payment_method_is_called(
        ExistingOtherPaymentInfo $existingOtherPaymentInfo
    ): void {
        $this->assertEquals('ewallet', $existingOtherPaymentInfo->paymentType());
    }

    /**
     * @test
     * @param ExistingOtherPaymentInfo $existingOtherPaymentInfo ExistingOtherPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_array_with_exact_payment_template_id_when_to_array_is_called(
        ExistingOtherPaymentInfo $existingOtherPaymentInfo
    ): void {
        $this->assertEquals($this->paymentTemplateId, $existingOtherPaymentInfo->toArray()['paymentTemplateId']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_payment_type_provided()
    {
        $this->expectException(UnsupportedPaymentTypeException::class);

        ExistingOtherPaymentInfo::create($this->paymentTemplateId, '',null);
    }
}
