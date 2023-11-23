<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\ObfuscatedData;
use Tests\UnitTestCase;

class ExistingCCPaymentInfoTest extends UnitTestCase
{
    /** @var string */
    protected $cardHash = 'testCardHashForTestPurpose';

    /** @var string */
    protected $paymentTemplateId = 'some-uuid-asdi-asdasd-asd-asddd';
    /**
     * @test
     * @return ExistingCCPaymentInfo
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_return_a_valid_object_if_the_correct_data_is_provided(): ExistingCCPaymentInfo
    {
        $existingCCPaymentInfo = ExistingCCPaymentInfo::create(
            $this->cardHash,
            $this->paymentTemplateId,
            null,
            []
        );
        $this->assertInstanceOf(ExistingCCPaymentInfo::class, $existingCCPaymentInfo);

        return $existingCCPaymentInfo;
    }

    /**
     * @test
     * @param ExistingCCPaymentInfo $existingCCPaymentInfo ExistingCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_card_hash_method_is_called(
        ExistingCCPaymentInfo $existingCCPaymentInfo
    ): void {
        $this->assertEquals($this->cardHash, $existingCCPaymentInfo->cardHash());
    }

    /**
     * @test
     * @param ExistingCCPaymentInfo $existingCCPaymentInfo ExistingCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_correct_value_when_payment_method_is_called(
        ExistingCCPaymentInfo $existingCCPaymentInfo
    ): void {
        $this->assertEquals('cc', $existingCCPaymentInfo->paymentType());
    }

    /**
     * @test
     * @param ExistingCCPaymentInfo $existingCCPaymentInfo ExistingCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_an_array_with_obfuscated_card_hash_data_when_to_array_is_called(
        ExistingCCPaymentInfo $existingCCPaymentInfo
    ): void {
        $this->assertEquals(ObfuscatedData::OBFUSCATED_STRING, $existingCCPaymentInfo->toArray()['cardHash']);
    }

    /**
     * @test
     * @param ExistingCCPaymentInfo $existingCCPaymentInfo ExistingCCPaymentInfo
     * @depends it_should_return_a_valid_object_if_the_correct_data_is_provided
     * @return void
     */
    public function it_should_return_an_array_with_exact_payment_template_id_when_to_array_is_called(
        ExistingCCPaymentInfo $existingCCPaymentInfo
    ): void {
        $this->assertEquals($this->paymentTemplateId, $existingCCPaymentInfo->toArray()['paymentTemplateId']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Throwable
     */
    public function it_should_throw_invalid_payment_info_exception_when_empty_card_hash_provided()
    {
        $this->expectException(InvalidPaymentInfoException::class);

        ExistingCCPaymentInfo::create('', $this->paymentTemplateId, null, []);
    }
}
