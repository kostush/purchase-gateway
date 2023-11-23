<?php

namespace Tests\Integration\PurchaseGateway\Domain\Services;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonType;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\LegacyImportService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineBundleProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use Tests\IntegrationTestCase;

class LegacyImportServiceTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws Exception
     */
    public function it_should_return_purchase_processed_event_when_call_legacy_end_point_without_proper_data(): void
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

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));
        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $legacyService = new LegacyImportService(
            $transactionService,
            $bundleRepository,
            $paymentTemplate,
            $configService
        );

        $eventReceived = $legacyService->handlerLegacyImportByApiEndPoint($purchaseProcessedEvent);

        $this->assertInstanceOf(PurchaseProcessed::class, $eventReceived);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_empty_array_when_response_http_code_not_received_200_from_legacy_import_api(): void
    {
        $this->markTestSkipped('Legacy started return 500, needs to be investigated on Legacy side');

        $legacyService = new LegacyImportService(
            $this->createMock(TransactionService::class),
            $this->createMock(DoctrineBundleProjectionRepository::class),
            $this->createMock(PaymentTemplateTranslatingService::class),
            $this->createMock(ConfigService::class)
        );

        $response = $legacyService->legacyCURLCall([]);

        $this->assertIsArray($response);
        $this->assertEmpty($response);
        $this->assertEquals([], $response);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_purchase_processed_event_marked_as_imported_by_api_when_successful_legacy_import_by_api_happened(): void
    {
        $eventBody              = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();
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

        $configService = $this->createMock(ConfigService::class);
        $configService->method('getSite')->willReturn($this->createMock(Site::class));
        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $legacyService = $this->getMockBuilder(LegacyImportService::class)
            ->setConstructorArgs(
                [
                    $transactionService,
                    $bundleRepository,
                    $paymentTemplate,
                    $configService
                ]
            )
            ->onlyMethods(['legacyCURLCall'])
            ->getMock();

        $legacyService->expects($this->any())
            ->method('legacyCURLCall')
            ->willReturn($this->createCurlLegacyImportReponse());
        $response = $legacyService->handlerLegacyImportByApiEndPoint($purchaseProcessedEvent);

        $this->assertTrue($response->isImportedByApi());
    }
}