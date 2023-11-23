<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\TransactionAlreadyProcessedException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddQyssoBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\ApiException;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use ProbillerNG\TransactionServiceClient\ObjectSerializer;
use Tests\UnitTestCase;

class AddQyssoBillerInteractionAdapterTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function it_should_return_an_qysso_biller_interaction_object(): void
    {
        $client = $this->createMock(TransactionServiceClient::class);
        $client->method('addQyssoBillerInteraction')
            ->willReturn(
                new InlineResponse200(
                    [
                        'status'        => 'approved',
                        'paymentType'   => 'cc',
                        'paymentMethod' => 'visa'
                    ]
                )
            );

        $adapter = new AddQyssoBillerInteractionAdapter(
            $client,
            $this->createMock(TransactionTranslator::class)
        );

        $result = $adapter->performAddQyssoBillerInteraction(
            TransactionId::create(),
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(QyssoBillerInteraction::class, $result);
    }
}
