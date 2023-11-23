<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumeEventCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use ProBillerNG\ServiceBus\ServiceBus;
use Tests\UnitTestCase;

class CreateMemberProfileEnrichedEventCommandHandlerTest extends UnitTestCase
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

        $this->handler = $this->getMockBuilder(CreateMemberProfileEnrichedEventCommandHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'createPurchaseEnrichedEvent',
                    'createBundleRebillEvent',
                    'retrieveTransactionData',
                    'serviceBusFactory',
                    'bundleRepository',
                    'retrieveSite'
                ]
            )
            ->getMock();
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function execute_should_not_create_integration_event_if_no_subscription_created(): void
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(['subscriptionId' => '']);
        /** @var ConsumeEventCommand|MockObject $handler */
        $eventMock = $this->createMock(ConsumeEventCommand::class);
        $eventMock->method('eventBody')->willReturn(
            json_encode(
                $eventBody
            )
        );

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

        $this->handler->method('bundleRepository')->willReturn($bundleRepository);
        $this->handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $this->handler->expects($this->never())->method('retrieveTransactionData');

        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function execute_should_create_integration_event(): void
    {
        /** @var ConsumeEventCommand|MockObject $handler */
        $eventMock = $this->createMock(ConsumeEventCommand::class);
        $eventMock->method('eventBody')->willReturn(
            json_encode(
                $this->createPurchaseProcessedWithRocketgateNewPaymentEventData()
            )
        );

        $transactionData = $this->createMock(RetrieveTransactionResult::class);
        $transactionData->method('transactionInformation')->willReturn(
            $this->createMock(TransactionInformation::class)
        );
        $this->handler->method('retrieveTransactionData')->willReturn($transactionData);

        $this->handler->expects($this->once())->method('createPurchaseEnrichedEvent');
        $this->handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function execute_should_retrieve_transaction_information(): void
    {
        /** @var ConsumeEventCommand|MockObject $handler */
        $eventMock = $this->createMock(ConsumeEventCommand::class);
        $eventMock->method('eventBody')->willReturn(
            json_encode(
                $this->createPurchaseProcessedWithRocketgateNewPaymentEventData()
            )
        );

        $transactionData = $this->createMock(RetrieveTransactionResult::class);
        $transactionData->method('transactionInformation')->willReturn(
            $this->createMock(TransactionInformation::class)
        );
        $this->handler->expects($this->once())->method('retrieveTransactionData')
            ->willReturn($transactionData);


        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function create_purchase_enriched_event_should_publish_integration_event_on_service_bus()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(['crossSalePurchaseData' => []]);
        $event     = PurchaseProcessed::createFromJson(
            json_encode(
                $eventBody
            )
        );

        /** @var MockObject|RetrieveTransactionResult $transactionResult */
        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('rebillFrequency')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionResult = $this->createMock(RetrieveTransactionResult::class);
        $transactionResult->method('transactionInformation')->willReturn(
            $transactionInformation
        );

        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->once())->method('publish');
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);
        $this->handler->method('serviceBusFactory')->willReturn(
            $serviceBusFactory
        );

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
        $this->handler->method('bundleRepository')->willReturn($bundleRepository);

        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('createPurchaseEnrichedEvent');
        $method->setAccessible(true);

        $method->invoke(
            $this->handler,
            $event,
            $transactionResult->transactionInformation()
        );
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function execute_should_create_integration_event_for_qysso_rebill(): void
    {
        /** @var ConsumeEventCommand|MockObject $handler */
        $eventMock = $this->createMock(ConsumeEventCommand::class);
        $eventMock->method('eventBody')->willReturn(
            json_encode(
                $this->createPurchaseProcessedWithQyssoEventData()
            )
        );

        $transactionData = $this->createMock(QyssoRetrieveTransactionResult::class);
        $transactionData->method('transactionInformation')->willReturn(
            $this->createMock(TransactionInformation::class)
        );
        $transactionData->method('type')->willReturn(QyssoRetrieveTransactionResult::TYPE_REBILL);
        $this->handler->method('retrieveTransactionData')->willReturn($transactionData);

        $this->handler->expects($this->once())->method('createBundleRebillEvent');
        $this->handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }
}
