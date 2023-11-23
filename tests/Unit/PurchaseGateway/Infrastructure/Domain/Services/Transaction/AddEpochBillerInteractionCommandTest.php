<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddEpochBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddEpochBillerInteractionCommand;
use Tests\UnitTestCase;

class AddEpochBillerInteractionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(AddEpochBillerInteractionAdapter::class);
        $adapterMock->method('performAddEpochBillerInteraction')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            AddEpochBillerInteractionCommand::class,
            $adapterMock,
            TransactionId::create(),
            SessionId::create(),
            []
        );

        $command->execute();
    }
}
