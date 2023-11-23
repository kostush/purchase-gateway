<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\Event;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseIntegrationEventBuilder;
use ProBillerNG\PurchaseGateway\Application\Services\Event\QyssoDebitPurchaseImportEvent;
use ProBillerNG\PurchaseGateway\Application\Services\Event\QyssoDebitRebillImportEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use Tests\UnitTestCase;

class PurchaseIntegrationEventBuilderTest extends UnitTestCase
{
    /**
     * @test
     * @return PurchaseEvent
     * @throws CannotCreateIntegrationEventException
     * @throws Exception
     */
    public function it_should_throw_a_cannot_create_integration_event_exception_if_biller_or_payment_type_not_mapped()
    {
        $retrieveTransactionResultMock = $this->createMock(RetrieveTransactionResult::class);
        $retrieveTransactionResultMock->method('paymentType')->willReturn('cc');
        $retrieveTransactionResultMock->method('billerId')->willReturn('unmapped');

        $this->expectException(CannotCreateIntegrationEventException::class);

        $bundle = $this->createMock(Bundle::class);
        $bundle->method('isRequireActiveContent')->willReturn(false);

        $purchaseIntegrationEventBuilder = PurchaseIntegrationEventBuilder::build(
            $retrieveTransactionResultMock,
            $this->createMock(PurchaseProcessed::class)
        );
        return $purchaseIntegrationEventBuilder;
    }

    /**
     * @test
     * @return void
     * @throws CannotCreateIntegrationEventException
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_return_a_purchase_event_if_correct_data_is_sent()
    {
        $rocketgateCCRetrieveTransactionResultMock = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $rocketgateCCRetrieveTransactionResultMock->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $rocketgateCCRetrieveTransactionResultMock->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);

        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $purchaseIntegrationEventBuilder = PurchaseIntegrationEventBuilder::build(
            $rocketgateCCRetrieveTransactionResultMock,
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(PurchaseEvent::class, $purchaseIntegrationEventBuilder);
    }

    /**
     * @test
     * @return void
     * @throws CannotCreateIntegrationEventException
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_return_a_purchase_event_if_correct_data_is_sent_without_tax_info()
    {
        $rocketgateCCRetrieveTransactionResultMock = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $rocketgateCCRetrieveTransactionResultMock->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $rocketgateCCRetrieveTransactionResultMock->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);

        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $purchaseIntegrationEventBuilder = PurchaseIntegrationEventBuilder::build(
            $rocketgateCCRetrieveTransactionResultMock,
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(PurchaseEvent::class, $purchaseIntegrationEventBuilder);
    }

    /**
     * @test
     * @return void
     * @throws CannotCreateIntegrationEventException
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_return_qysso_debit_purchase_import_event_if_correct_data_is_sent(): void
    {
        $qyssoRetrieveTransactionResultMock = $this->createMock(QyssoRetrieveTransactionResult::class);
        $qyssoRetrieveTransactionResultMock->method('paymentType')->willReturn('banktransfer');
        $qyssoRetrieveTransactionResultMock->method('billerId')->willReturn(QyssoBiller::BILLER_ID);

        $eventBody = $this->createPurchaseProcessedWithQyssoEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $purchaseIntegrationEventBuilder = PurchaseIntegrationEventBuilder::build(
            $qyssoRetrieveTransactionResultMock,
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(QyssoDebitPurchaseImportEvent::class, $purchaseIntegrationEventBuilder);
    }

    /**
     * @test
     * @return void
     * @throws CannotCreateIntegrationEventException
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_return_qysso_debit_rebill_import_event_if_correct_data_is_sent(): void
    {
        $qyssoRetrieveTransactionResultMock = $this->createMock(QyssoRetrieveTransactionResult::class);
        $qyssoRetrieveTransactionResultMock->method('paymentType')->willReturn('banktransfer');
        $qyssoRetrieveTransactionResultMock->method('billerId')->willReturn(QyssoBiller::BILLER_ID);
        $qyssoRetrieveTransactionResultMock->method('type')->willReturn(QyssoRetrieveTransactionResult::TYPE_REBILL);

        $eventBody = $this->createPurchaseProcessedWithQyssoEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $purchaseIntegrationEventBuilder = PurchaseIntegrationEventBuilder::build(
            $qyssoRetrieveTransactionResultMock,
            $purchaseProcessedEvent
        );

        $this->assertInstanceOf(QyssoDebitRebillImportEvent::class, $purchaseIntegrationEventBuilder);
    }
}
