<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use Tests\UnitTestCase;

class EpochBillerInteractionTest extends UnitTestCase
{
    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_create_an_epoch_biller_interaction(): array
    {
        $epochBillerInteraction = EpochBillerInteraction::create(
            TransactionId::createFromString('25d7b032-9381-4174-a53e-fce9d9299f62'),
            'approved',
            'cc',
            'visa'
        );

        $this->assertInstanceOf(EpochBillerInteraction::class, $epochBillerInteraction);

        return $epochBillerInteraction->toArray();
    }

    /**
     * @test
     * @param array $epochBillerInteraction EpochBillerInteraction
     * @return void
     * @depends it_should_create_an_epoch_biller_interaction
     */
    public function it_should_have_a_transaction_id(array $epochBillerInteraction): void
    {
        $this->assertSame('25d7b032-9381-4174-a53e-fce9d9299f62', $epochBillerInteraction['transactionId']);
    }

    /**
     * @test
     * @param array $epochBillerInteraction EpochBillerInteraction
     * @return void
     * @depends it_should_create_an_epoch_biller_interaction
     */
    public function it_should_have_a_status(array $epochBillerInteraction): void
    {
        $this->assertSame('approved', $epochBillerInteraction['status']);
    }

    /**
     * @test
     * @param array $epochBillerInteraction EpochBillerInteraction
     * @return void
     * @depends it_should_create_an_epoch_biller_interaction
     */
    public function it_should_have_a_payment_type(array $epochBillerInteraction): void
    {
        $this->assertSame('cc', $epochBillerInteraction['paymentType']);
    }

    /**
     * @test
     * @param array $epochBillerInteraction EpochBillerInteraction
     * @return void
     * @depends it_should_create_an_epoch_biller_interaction
     */
    public function it_should_have_a_payment_method(array $epochBillerInteraction): void
    {
        $this->assertSame('visa', $epochBillerInteraction['paymentMethod']);
    }
}
