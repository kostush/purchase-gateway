<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotCreateIntegrationEventException;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\PurchaseEntityCannotBeCreatedException;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CreatePaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\LegacyImportService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use Tests\UnitTestCase;

class PurchaseServiceTest extends UnitTestCase
{

    /**
     * @var MockObject|CreatePaymentTemplateService
     */
    private $createPaymentTemplateService;

    protected function setUp(): void
    {
        $this->createPaymentTemplateService = $this->createMock(CreatePaymentTemplateService::class);

        parent::setUp();
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws PurchaseEntityCannotBeCreatedException
     */
    public function it_should_return_null_if_is_processed_is_set_to_false(): void
    {
        $repo            = $this->createMock(PurchaseRepository::class);
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('isProcessed')->willReturn(false);

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);

        $purchaseEntity = $purchaseService->createPurchaseEntity($purchaseProcess, $site);

        $this->assertNull($purchaseEntity);
    }


    /**
     * @test
     * @return void
     * @throws Exception
     * @throws PurchaseEntityCannotBeCreatedException
     */
    public function it_should_throw_purchase_entity_cannot_be_created_exception(): void
    {
        $this->expectException(PurchaseEntityCannotBeCreatedException::class);

        $repo            = $this->createMock(PurchaseRepository::class);
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('isProcessed')->willReturn(true);
        $purchaseProcess->method('wasMainItemPurchaseSuccessful')->willReturn(true);
        $purchaseProcess->method('wasMainItemPurchaseSuccessfulOrPending')->willReturn(true);
        $purchaseProcess->method('buildMemberId')->willThrowException(new \Exception());
        $purchaseProcess->method('memberId')->willReturn($this->faker->uuid);

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('lastTransactionId')->willReturn(TransactionId::create());
        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('lastTransactionId')->willReturn(TransactionId::create());
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($initializedItem);
        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);

        $purchaseService->createPurchaseEntity($purchaseProcess, $site);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws CannotCreateIntegrationEventException
     */
    public function it_should_throw_purchase_entity_cannot_be_created_exception_for_third_party(): void
    {
        $this->expectException(PurchaseEntityCannotBeCreatedException::class);

        $repo            = $this->createMock(PurchaseRepository::class);
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('isProcessed')->willReturn(true);
        $purchaseProcess->method('wasMainItemPurchaseSuccessful')->willReturn(true);
        $purchaseProcess->method('wasMainItemPurchaseSuccessfulOrPending')->willReturn(true);
        $purchaseProcess->method('buildMemberId')->willThrowException(new \Exception());

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $purchaseService->createPurchaseEntityForThirdParty($purchaseProcess);
    }
}
