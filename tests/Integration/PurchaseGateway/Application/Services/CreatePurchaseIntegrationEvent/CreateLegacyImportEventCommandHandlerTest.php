<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Base\Domain\InvalidCommandException;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\ConsumeEventCommand;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochCCRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ReflectionClass;
use ReflectionException;
use Tests\IntegrationTestCase;

class CreateLegacyImportEventCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @param $eventBody
     * @return MockObject|DoctrineBundleProjectionRepository
     * @throws \Exception
     */
    private function mockBundleRepository($eventBody)
    {
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

        return $bundleRepository;
    }
    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @throws \Exception
     */
    public function it_should_publish_an_integration_event_for_main_purchase_and_cross_sales_performed_with_rocketgate()
    {
        if (config('app.feature.legacy_api_import')) {
            $this->markTestSkipped('This test should be skipped when legacy api import is turn on because there will be no integration event in that case.');
        }

        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();
        $event     = $this->createMock(ConsumeEventCommand::class);
        $event->method('eventBody')
            ->willReturn(json_encode($eventBody));

        $transactionResult = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionResult->method('paymentType')->willReturn(NewCCPaymentInfo::PAYMENT_TYPE);
        $transactionResult->method('billerId')->willReturn(RocketgateBiller::BILLER_ID);
        $billerTransactions = new BillerTransactionCollection();
        $billerTransactions->add(
            RocketgateBillerTransaction::create(
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->uuid,
                'auth'
            )
        );
        $billerTransactions->add(
            RocketgateBillerTransaction::create(
                $this->faker->uuid,
                $this->faker->uuid,
                $this->faker->uuid,
                'sale'
            )
        );
        $transactionResult->method('billerTransactions')->willReturn($billerTransactions);
        $transactionResult->method('securedWithThreeD')->willReturn(true);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);



        /** @var CreateLegacyImportEventCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $this->mockBundleRepository($eventBody),
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();
        $handler->expects($this->exactly(1))->method('publishIntegrationEvent');
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new ReflectionClass(CreateLegacyImportEventCommandHandler::class);
        $method     = $reflection->getMethod('execute');
        $method->setAccessible(true);

        $method->invoke($handler, $event);
    }

    /**
     * @test
     * @throws InvalidCommandException
     * @throws Exception
     * @throws CannotCreateIntegrationEventException
     */
    public function it_should_publish_an_integration_event_for_main_purchase_and_cross_sales_performed_with_epoch()
    {
        if (config('app.feature.legacy_api_import')) {
            $this->markTestSkipped('This test should be skipped when legacy api import is turn on because there will be no integration event in that case.');
        }

        $eventBody = $this->createPurchaseProcessedNewPaymentWithEpochEventData();
        $command = ConsumeEventCommand::create(json_encode($eventBody));

        $transactionResult = $this->createMock(EpochCCRetrieveTransactionResult::class);
        $transactionResult->method('paymentType')->willReturn(CCPaymentInfo::PAYMENT_TYPE);
        $transactionResult->method('billerId')->willReturn(EpochBiller::BILLER_ID);
        $transactionResult->method('transactionInformation')->willReturn($this->createMock(NewCCTransactionInformation::class));
        $billerTransactions = new BillerTransactionCollection();
        $billerTransactions->add(
            EpochBillerTransaction::create(
                'piCode',
                'billerMemberid',
                'billerTransactionId',
                'ans'
            )
        );
        $transactionResult->method('billerTransactions')->willReturn($billerTransactions);
        $transactionResult->method('securedWithThreeD')->willReturn(false);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionResult);

        /** @var CreateLegacyImportEventCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $this->mockBundleRepository($eventBody),
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class)
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();
        $handler->expects($this->exactly(1))->method('publishIntegrationEvent');
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $handler->execute($command);
    }
}
