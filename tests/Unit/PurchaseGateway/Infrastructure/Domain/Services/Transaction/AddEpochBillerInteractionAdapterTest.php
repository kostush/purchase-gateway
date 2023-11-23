<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddEpochBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use Tests\UnitTestCase;

class AddEpochBillerInteractionAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_return_an_epoch_biller_interaction_object(): void
    {
        $client = $this->createMock(TransactionServiceClient::class);
        $client->method('addEpochBillerInteraction')
            ->willReturn(
                new InlineResponse200(
                    [
                        'status'        => 'approved',
                        'paymentType'   => 'cc',
                        'paymentMethod' => 'visa'
                    ]
                )
            );

        $adapter = new AddEpochBillerInteractionAdapter(
            $client,
            $this->createMock(TransactionTranslator::class)
        );

        $result = $adapter->performAddEpochBillerInteraction(
            TransactionId::create(),
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(EpochBillerInteraction::class, $result);
    }
}
