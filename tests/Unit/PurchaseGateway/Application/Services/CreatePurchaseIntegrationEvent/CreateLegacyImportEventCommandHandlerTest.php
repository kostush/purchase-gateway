<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumeEventCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\Event\PurchaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use Tests\UnitTestCase;

class CreateLegacyImportEventCommandHandlerTest extends UnitTestCase
{
    /**
     * @var MockObject|CreateLegacyImportEventCommandHandler
     */
    private $handler;

    /**
     * Setup method
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'handlePurchase',
                    'retrieveTransactionData',
                    'handleIntegrationEvent',
                    'createPurchaseIntegrationEvent',
                    'publishIntegrationEvent',
                    'bundleRepository',
                    'siteRepository'
                ]
            )
            ->getMock();
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function execute_should_handle_purchase(): void
    {

        if (config('app.feature.legacy_api_import')) {
          $this->markTestSkipped('This test will be skipped when legacy api import is turn on because rabbitMQ will not be used in that case so no event will be published');
        }

        /** @var ConsumeEventCommand|MockObject $handler */
        $eventMock = $this->createMock(ConsumeEventCommand::class);
        $eventMock->method('eventBody')->willReturn(
            json_encode(
                $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
                    [
                        'transactionCollection' => [
                            [
                                'state'         => 'approved',
                                'transactionId' => $this->faker->uuid
                            ]
                        ]
                    ]
                )
            )
        );

        $this->handler->expects($this->once())->method('handlePurchase');

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function handle_integration_event_should_return_if_no_transaction_id_on_event()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessedEventMock->method('transactionCollection')->willReturn([]);

        $paymentTemplateMock = $this->createMock(PaymentTemplate::class);

        $this->handler->expects($this->never())->method('retrieveTransactionData');

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('handleIntegrationEvent');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessedEventMock, $paymentTemplateMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function handle_integration_event_should_return_if_transaction_aborted()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessedEventMock->method('lastTransaction')->willReturn(
            [
                'transactionId' => 'test',
                'state'         => Transaction::STATUS_ABORTED
            ]
        );

        $paymentTemplateMock = $this->createMock(PaymentTemplate::class);

        $this->handler->expects($this->never())->method('retrieveTransactionData');

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('handleIntegrationEvent');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessedEventMock, $paymentTemplateMock);
    }


    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function create_purchase_integration_event_should_return_a_purchase_event_with_one_item_if_no_cross_sales()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(['crossSalePurchaseData' => []]);

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $transactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionResult->method('paymentType')->willReturn(NewCCPaymentInfo::PAYMENT_TYPE);
        $transactionResult->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $bundleRepository = $this->createMock(DoctrineBundleProjectionRepository::class);
        $bundleRepository->method('findBundleByIds')->willReturn(
            [
                $eventBody['bundle_id'] => Bundle::create(
                    BundleId::createFromString($eventBody['bundle_id']),
                    true,
                    AddonId::createFromString($eventBody['add_on_id']),
                    AddonType::create(AddonType::CONTENT)
                )
            ]
        );

        $bundleRepository->method('findBundleByBundleAddon')->willReturn(
            Bundle::create(
                BundleId::createFromString($eventBody['bundle_id']),
                true,
                AddonId::createFromString($eventBody['add_on_id']),
                AddonType::create(AddonType::CONTENT)
            )
        );

        /** @var CreateLegacyImportEventCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('createPurchaseIntegrationEvent');
        $method->setAccessible(true);

        /** @var PurchaseEvent $event */
        $event = $method->invoke($handler, $purchaseProcessedEvent);

        $this->assertEquals(1, count($event->toArray()['items']));
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function create_purchase_integration_event_should_return_a_purchase_event_with_one_item_if_one_cross_sales_aborted()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
            ['transactionCollectionCrossSale' => [['state' => Transaction::STATUS_ABORTED]]]
        );

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $transactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionResult->method('paymentType')->willReturn(NewCCPaymentInfo::PAYMENT_TYPE);
        $transactionResult->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $bundleRepository = $this->createMock(DoctrineBundleProjectionRepository::class);
        $bundleRepository->method('findBundleByIds')->willReturn(
            [
                $eventBody['bundle_id'] => Bundle::create(
                    BundleId::createFromString($eventBody['bundle_id']),
                    true,
                    AddonId::createFromString($eventBody['add_on_id']),
                    AddonType::create(AddonType::CONTENT)
                )
            ]
        );

        $bundleRepository->method('findBundleByBundleAddon')->willReturn(
            Bundle::create(
                BundleId::createFromString($eventBody['bundle_id']),
                true,
                AddonId::createFromString($eventBody['add_on_id']),
                AddonType::create(AddonType::CONTENT)
            )
        );

        /** @var CreateLegacyImportEventCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('createPurchaseIntegrationEvent');
        $method->setAccessible(true);

        /** @var PurchaseEvent $event */
        $event = $method->invoke($handler, $purchaseProcessedEvent);

        $this->assertEquals(1, count($event->toArray()['items']));
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function create_purchase_integration_event_should_return_a_purchase_event_with_two_item_if_one_cross_sales_purchased()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        $purchaseProcessedEvent = PurchaseProcessed::createFromJson(json_encode($eventBody));

        $transactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionResult->method('paymentType')->willReturn(NewCCPaymentInfo::PAYMENT_TYPE);
        $transactionResult->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $bundleRepository = $this->createMock(DoctrineBundleProjectionRepository::class);
        $bundleRepository->method('findBundleByIds')->willReturn(
            [
                $eventBody['bundle_id'] => Bundle::create(
                    BundleId::createFromString($eventBody['bundle_id']),
                    true,
                    AddonId::createFromString($eventBody['add_on_id']),
                    AddonType::create(AddonType::CONTENT)
                )
            ]
        );

        $bundleRepository->method('findBundleByBundleAddon')->willReturn(
            Bundle::create(
                BundleId::createFromString($eventBody['bundle_id']),
                true,
                AddonId::createFromString($eventBody['add_on_id']),
                AddonType::create(AddonType::CONTENT)
            )
        );

        /** @var CreateLegacyImportEventCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('createPurchaseIntegrationEvent');
        $method->setAccessible(true);

        /** @var PurchaseEvent $event */
        $event = $method->invoke($handler, $purchaseProcessedEvent);

        $this->assertEquals(2, count($event->toArray()['items']));
    }
}
