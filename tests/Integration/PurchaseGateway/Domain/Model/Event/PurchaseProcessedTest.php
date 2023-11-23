<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Domain\Model\Event;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\PurchaseEntityCannotBeCreatedException;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\CreatePaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\LegacyImportService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ReflectionException;
use Tests\IntegrationTestCase;

class PurchaseProcessedTest extends IntegrationTestCase
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidAmountException
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws ReflectionException
     * @return PurchaseProcessed
     */
    public function create_method_should_return_a_valid_purchase_processed_object(): PurchaseProcessed
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME,
            null
        );

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);
        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);


        $repo = $this->createMock(PurchaseRepository::class);
        $repo->expects($this->once())->method('add');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $purchaseEntity = $purchaseService->createPurchaseEntity($purchaseProcess, $site);

        $purchaseProcessedEvent = PurchaseProcessed::create(
            $purchaseProcess,
            $purchaseEntity
        );

        $this->assertInstanceOf(PurchaseProcessed::class, $purchaseProcessedEvent);

        return $purchaseProcessedEvent;
    }
}
