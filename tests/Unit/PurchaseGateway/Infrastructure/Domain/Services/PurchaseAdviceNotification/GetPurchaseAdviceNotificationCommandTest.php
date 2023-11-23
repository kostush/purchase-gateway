<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\FraudAdvice;

use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\GetPurchaseAdviceNotificationCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification\PurchaseAdviceNotificationAdapter;
use Ramsey\Uuid\Uuid;
use Tests\UnitTestCase;

class GetPurchaseAdviceNotificationCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_fallback_false_for_exception(): void
    {
        $adapterMock = $this->createMock(PurchaseAdviceNotificationAdapter::class);
        $adapterMock->method('getAdvice')->willThrowException(new \Exception());

        $command = $this->getCircuitBreakerCommandFactory()->getCommand(
            GetPurchaseAdviceNotificationCommand::class,
            $adapterMock,
            Uuid::uuid4()->toString(),
            'vat',
            Uuid::uuid4()->toString(),
            RocketgateBiller::BILLER_NAME,
            'new',
            Uuid::uuid4()->toString()
        );

        $notificationAdvice = $command->execute();
        $this->assertFalse($notificationAdvice);
    }
}
