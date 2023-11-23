<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionAdapter;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionCommand;
use Tests\UnitTestCase;

class CompleteThreeDTransactionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_perform_complete_threeD(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(CompleteThreeDTransactionAdapter::class);
        $adapterMock->method('performCompleteThreeDTransaction')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            CompleteThreeDTransactionCommand::class,
            $adapterMock,
            TransactionId::create(),
            'SimulatedPARES10001000E00B000',
            'md',
            SessionId::create()
        );

        $command->execute();
    }
}
