<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CreatePerformTransactionCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\GetTransactionDataByAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\RetrieveGetTransactionDataByCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewCardPerformTransactionAdapter;
use Tests\UnitTestCase;

class CreatePerformTransactionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_perform_transaction(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(NewCardPerformTransactionAdapter::class);
        $adapterMock->method('performTransaction')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            CreatePerformTransactionCommand::class,
            $adapterMock,
            SiteId::create(),
            new RocketgateBiller(),
            new CurrencyCode('USD'),
            $this->createMock(UserInfo::class),
            $this->createMock(BundleSingleChargeInformation::class),
            $this->createMock(CCPaymentInfo::class),
            $this->createMock(BillerMapping::class),
            SessionId::create(),
            $this->createMock(BinRouting::class)
        );

        $command->execute();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_get_transaction_data_by(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(GetTransactionDataByAdapter::class);
        $adapterMock->method('getTransactionDataBy')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            RetrieveGetTransactionDataByCommand::class,
            $adapterMock,
            TransactionId::create(),
            SessionId::create()
        );

        $command->execute();
    }
}
