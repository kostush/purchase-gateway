<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use Tests\UnitTestCase;

class EpochBillerFieldsTest extends UnitTestCase
{
    /**
     * @test
     * @return EpochBillerFields
     */
    public function it_should_return_an_epoch_biller_fields_object(): EpochBillerFields
    {
        $result = EpochBillerFields::create(
            'clientId',
            'clientKey',
            'clientVerificationKey'
        );

        $this->assertInstanceOf(EpochBillerFields::class, $result);

        return $result;
    }

    /**
     * @test
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_epoch_biller_fields_object
     */
    public function it_should_have_the_correct_client_id(EpochBillerFields $billerFields): void
    {
        $this->assertSame('clientId', $billerFields->clientId());
    }

    /**
     * @test
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_epoch_biller_fields_object
     */
    public function it_should_have_the_correct_client_key(EpochBillerFields $billerFields): void
    {
        $this->assertSame('clientKey', $billerFields->clientKey());
    }

    /**
     * @test
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_epoch_biller_fields_object
     */
    public function it_should_have_the_correct_client_verification_key(EpochBillerFields $billerFields): void
    {
        $this->assertSame('clientVerificationKey', $billerFields->clientVerificationKey());
    }

    /**
     * @test
     * @param EpochBillerFields $billerFields biller fields
     * @return void
     * @depends it_should_return_an_epoch_biller_fields_object
     */
    public function it_should_return_the_correct_array_when_to_array_method_is_called(EpochBillerFields $billerFields
    ): void {
        $this->assertSame(
            [
                'clientId'              => 'clientId',
                'clientKey'             => 'clientKey',
                'clientVerificationKey' => 'clientVerificationKey'
            ],
            $billerFields->toArray()
        );
    }
}
