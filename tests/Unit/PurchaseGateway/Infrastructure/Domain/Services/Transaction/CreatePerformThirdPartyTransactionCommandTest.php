<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionAdapter;
use Odesk\Phystrix\Exception\RuntimeException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CreatePerformThirdPartyTransactionCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ThirdPartyPerformTransactionAdapter;
use Tests\UnitTestCase;

class CreatePerformThirdPartyTransactionCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_an_exception_when_the_circuit_is_opened_on_perform_third_party_transaction(): void
    {
        $this->expectException(RuntimeException::class);

        $adapterMock = $this->createMock(ThirdPartyPerformTransactionAdapter::class);
        $adapterMock->method('performTransaction')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            CreatePerformThirdPartyTransactionCommand::class,
            $adapterMock,
            $this->createSite(),
            [],
            new EpochBiller(),
            CurrencyCode::create('USD'),
            $this->createMock(UserInfo::class),
            $this->createMock(ChargeInformation::class),
            $this->createMock(CCPaymentInfo::class),
            $this->createMock(BillerMapping::class),
            SessionId::create(),
            $this->faker->url,
            null,
            null,
            null,
            null
        );

        $command->execute();
    }
}
