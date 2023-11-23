<?php

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Services\EpochBillerFieldsDataAdapter;
use Tests\UnitTestCase;

class EpochBillerFieldsDataAdapterTest extends UnitTestCase
{
    /**
     * @var array
     */
    protected $billerFields;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->billerFields = [
            'clientId'              => 'clientId',
            'clientKey'             => 'clientKey',
            'clientVerificationKey' => 'clientVerificationKey',
        ];
    }

    /**
     * @test
     * @return EpochBillerFields
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException
     */
    public function it_should_return_an_epoch_biller_fields_object_when_converting(): EpochBillerFields
    {
        $billerFieldsDataAdapter = new EpochBillerFieldsDataAdapter();

        /** @var EpochBillerFields $billerFields */
        $billerFields = $billerFieldsDataAdapter->convert($this->billerFields);

        $this->assertInstanceOf(EpochBillerFields::class, $billerFields);

        return $billerFields;
    }

    /**
     * @test
     * @depends it_should_return_an_epoch_biller_fields_object_when_converting
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     */
    public function converted_object_should_have_a_client_id(EpochBillerFields $billerFields): void
    {
        $this->assertEquals($billerFields->clientId(), $this->billerFields['clientId']);
    }

    /**
     * @test
     * @depends it_should_return_an_epoch_biller_fields_object_when_converting
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     */
    public function converted_object_should_have_a_client_key(EpochBillerFields $billerFields): void
    {
        $this->assertEquals($billerFields->clientKey(), $this->billerFields['clientKey']);
    }

    /**
     * @test
     * @depends it_should_return_an_epoch_biller_fields_object_when_converting
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     */
    public function converted_object_should_have_a_client_verification_key(EpochBillerFields $billerFields): void
    {
        $this->assertEquals($billerFields->clientVerificationKey(), $this->billerFields['clientVerificationKey']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\InvalidBillerFieldsDataException
     */
    public function it_should_throw_an_exception_if_invalid_data_provided(): void
    {
        $this->expectException(\Exception::class);

        $billerFieldsDataAdapter = new EpochBillerFieldsDataAdapter();

        $billerFieldsDataAdapter->convert(['wrongIndex' => '1234']);
    }
}
