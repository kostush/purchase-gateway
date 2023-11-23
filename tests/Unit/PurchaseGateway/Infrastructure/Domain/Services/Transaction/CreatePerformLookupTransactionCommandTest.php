<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\LookupThreeDThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\LookupThreeDTransactionCommand;
use Tests\UnitTestCase;

class CreatePerformLookupTransactionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_perform_complete_threeD(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(LookupThreeDThreeDTransactionAdapter::class);
        $adapterMock->method('lookupTransaction')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            LookupThreeDTransactionCommand::class,
            $adapterMock,
            TransactionId::create(),
            $this->createMock(PaymentInfo::class),
            $this->faker->url,
            $this->faker->uuid,
            'rocketgate',
            SessionId::create()
        );

        $command->execute();
    }
}
