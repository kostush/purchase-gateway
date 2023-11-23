<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use Exception;
use ProBillerNG\PurchaseGateway\Application\Services\Event\QyssoDebitPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use Tests\UnitTestCase;

class QyssoDebitPurchaseImportEventTest extends UnitTestCase
{
    /**
     * @test
     * @return QyssoDebitPurchaseImportEvent
     * @throws Exception
     */
    public function it_should_return_a_qysso_debit_purchase_import_event_object(): QyssoDebitPurchaseImportEvent
    {
        $memberInformation = $this->createMock(MemberInformation::class);
        $memberInformation->method('name')->willReturn('testName');

        $retrieveTransactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $retrieveTransactionResult->method('paymentType')->willReturn('banktransfer');
        $retrieveTransactionResult->method('billerName')->willReturn('qysso');
        $retrieveTransactionResult->method('currency')->willReturn('usd');
        $retrieveTransactionResult->method('memberInformation')->willReturn($memberInformation);
        $retrieveTransactionResult->method('paymentSubtype')->willReturn('zelle');

        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessedEvent->method('purchaseId')->willReturn($this->faker->uuid);
        $purchaseProcessedEvent->method('memberId')->willReturn($this->faker->uuid);
        $purchaseProcessedEvent->method('memberInfo')->willReturn(['email' => 'test@email.com']);
        $purchaseProcessedEvent->method('subscriptionUsername')->willReturn($this->faker->userName);
        $purchaseProcessedEvent->method('subscriptionPassword')->willReturn($this->faker->password);

        $qyssoDebitPurchaseImportEvent = new QyssoDebitPurchaseImportEvent(
            $retrieveTransactionResult,
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(QyssoDebitPurchaseImportEvent::class, $qyssoDebitPurchaseImportEvent);

        return $qyssoDebitPurchaseImportEvent;
    }

    /**
     * @test
     * @depends it_should_return_a_qysso_debit_purchase_import_event_object
     * @param QyssoDebitPurchaseImportEvent $qyssoDebitPurchaseImportEvent Qysso debit purchase import event.
     * @return void
     */
    public function it_should_contain_payment_method(QyssoDebitPurchaseImportEvent $qyssoDebitPurchaseImportEvent): void
    {
        $this->assertEquals('zelle', $qyssoDebitPurchaseImportEvent->paymentMethod());
    }

    /**
     * @test
     * @depends it_should_return_a_qysso_debit_purchase_import_event_object
     * @param QyssoDebitPurchaseImportEvent $qyssoDebitPurchaseImportEvent Qysso debit purchase import event.
     * @return void
     */
    public function it_should_contain_member_name(QyssoDebitPurchaseImportEvent $qyssoDebitPurchaseImportEvent): void
    {
        $this->assertEquals('testName', $qyssoDebitPurchaseImportEvent->memberName());
    }
}
