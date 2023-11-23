<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventBackupCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use Tests\IntegrationTestCase;

class CreateLegacyImportEventBackupCommandHandlerTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Exception
     */
    public function it_should_publish_one_event_for_main_purchase_cross_sales()
    {
        $eventBody = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();
        $event     = $this->createMock(ItemToWorkOn::class);
        $event->method('body')
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

        /** @var CreateLegacyImportEventBackupCommandHandler|MockObject $handler */
        $handler = $this->getMockBuilder(CreateLegacyImportEventBackupCommandHandler::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $this->createMock(PaymentTemplateTranslatingService::class),
                    $this->createMock(ServiceBusFactory::class),
                    $this->createMock(ConfigService::class),
                ]
            )
            ->onlyMethods(['publishIntegrationEvent', 'retrieveSite'])
            ->getMock();
        $handler->expects($this->exactly(1))->method('publishIntegrationEvent');
        $handler->method('retrieveSite')->willReturn($this->createMock(Site::class));

        $reflection = new \ReflectionClass(CreateLegacyImportEventBackupCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $event);
    }
}
