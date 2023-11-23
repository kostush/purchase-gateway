<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumeEventCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateMemberProfileEnrichedEventCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\Event\BundleRebillWasSuccessfulEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\QyssoRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;
use ProBillerNG\ServiceBus\ServiceBus;
use Tests\IntegrationTestCase;

class CreateMemberProfileEnrichedEventCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @return CreateMemberProfileEnrichedEventCommandHandler
     * @throws \ReflectionException
     */
    public function it_should_publish_an_event_on_service_bus()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(['crossSalePurchaseData' => []]);
        $event     = $this->createMock(ConsumeEventCommand::class);
        $event->method('eventBody')
            ->willReturn(json_encode($eventBody));

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

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->once())->method('publish');
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

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

        /** @var MockObject|CreateMemberProfileEnrichedEventCommandHandler $handler */
        $handler = $this->getMockBuilder(CreateMemberProfileEnrichedEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $serviceBusFactory,
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));
        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke(
            $handler,
            $event
        );

        return $handler;
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_publish_an_event_on_service_bus_with_bundle_rebill_was_successful_event(): void
    {
        $eventBody = $this->createPurchaseProcessedWithQyssoEventData();
        $event     = $this->createMock(ConsumeEventCommand::class);
        $event->method('eventBody')
            ->willReturn(json_encode($eventBody));

        /** @var MockObject|RetrieveTransactionResult $transactionResult */
        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('rebillFrequency')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_APPROVED);
        $transactionInformation->method('createdAt')->willReturn(new \DateTimeImmutable());

        $transactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $transactionResult->method('transactionInformation')->willReturn(
            $transactionInformation
        );
        $transactionResult->method('type')->willReturn(QyssoRetrieveTransactionResult::TYPE_REBILL);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->once())->method('publish');
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

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

        /** @var MockObject|CreateMemberProfileEnrichedEventCommandHandler $handler */
        $handler = $this->getMockBuilder(CreateMemberProfileEnrichedEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $serviceBusFactory,
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));
        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke(
            $handler,
            $event
        );
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_publish_an_event_on_service_bus_with_bundle_rebill_was_unsuccessful_event(): void
    {
        $eventBody = $this->createPurchaseProcessedWithQyssoEventData();
        $event     = $this->createMock(ConsumeEventCommand::class);
        $event->method('eventBody')
            ->willReturn(json_encode($eventBody));

        /** @var MockObject|RetrieveTransactionResult $transactionResult */
        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('rebillFrequency')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_DECLINED);
        $transactionInformation->method('createdAt')->willReturn(new \DateTimeImmutable());

        $transactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $transactionResult->method('transactionInformation')->willReturn(
            $transactionInformation
        );
        $transactionResult->method('type')->willReturn(QyssoRetrieveTransactionResult::TYPE_REBILL);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->once())->method('publish');
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

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

        /** @var MockObject|CreateMemberProfileEnrichedEventCommandHandler $handler */
        $handler = $this->getMockBuilder(CreateMemberProfileEnrichedEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $serviceBusFactory,
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));
        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke(
            $handler,
            $event
        );
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_not_publish_an_event_on_service_bus_for_an_aborted_rebill_transaction(): void
    {
        $eventBody = $this->createPurchaseProcessedWithQyssoEventData();
        $event     = $this->createMock(ConsumeEventCommand::class);
        $event->method('eventBody')
            ->willReturn(json_encode($eventBody));

        /** @var MockObject|RetrieveTransactionResult $transactionResult */
        $transactionInformation = $this->createMock(TransactionInformation::class);
        $transactionInformation->method('rebillStart')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('rebillFrequency')
            ->willReturn($this->faker->numberBetween(1, 365));
        $transactionInformation->method('status')->willReturn(Transaction::STATUS_ABORTED);
        $transactionInformation->method('createdAt')->willReturn(new \DateTimeImmutable());

        $transactionResult = $this->createMock(QyssoRetrieveTransactionResult::class);
        $transactionResult->method('transactionInformation')->willReturn(
            $transactionInformation
        );
        $transactionResult->method('type')->willReturn(QyssoRetrieveTransactionResult::TYPE_REBILL);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->never())->method('publish');
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

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

        /** @var MockObject|CreateMemberProfileEnrichedEventCommandHandler $handler */
        $handler = $this->getMockBuilder(CreateMemberProfileEnrichedEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $serviceBusFactory,
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));
        $reflection = new \ReflectionClass(CreateMemberProfileEnrichedEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke(
            $handler,
            $event
        );
    }
}
