<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\SimplifiedCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\SimplifiedCompleteThreeDTransactionCommand;
use Tests\UnitTestCase;

class SimplifiedCompleteThreeDTransactionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_perform_simplified_complete_threed(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(SimplifiedCompleteThreeDTransactionAdapter::class);
        $adapterMock->method('performSimplifiedCompleteThreeDTransaction')->willThrowException(new Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            SimplifiedCompleteThreeDTransactionCommand::class,
            $adapterMock,
            TransactionId::create(),
            'flag=17c6f59e222&id=64d98d86-61642f822233e7.53329385&invoiceID=aba9b991-61642f82223498.08058272&hash=4qEW12Qdl5%2FYxkCtRbZ%2FHT%2Bi1NM%3D',
            SessionId::create()
        );

        $command->execute();
    }
}
