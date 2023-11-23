<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Domain\Services;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\PurchaseEntityCannotBeCreatedException;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplateCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
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

class PurchaseServiceTest extends IntegrationTestCase
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
     * @return Purchase
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidAmountException
     * @throws ReflectionException
     */
    public function it_should_add_the_process_entity_to_repo(): Purchase
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess(['memberId' => $this->faker->uuid]);
        $purchaseProcess->resetMemberId();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);
        $repo->expects($this->once())->method('add');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);

        return $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );
    }

    /**
     * @test
     * @depends it_should_add_the_process_entity_to_repo
     * @param Purchase $purchaseEntity Purchase entity
     * @return void
     */
    public function it_should_return_a_valid_process_object(Purchase $purchaseEntity): void
    {
        $this->assertInstanceOf(Purchase::class, $purchaseEntity);
    }

    /**
     * @test
     * @return Purchase
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function it_should_add_the_process_entity_to_repo_for_third_party(): Purchase
    {
        $purchaseProcess = $this->createPurchaseProcess();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            EpochBiller::BILLER_NAME
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);
        $repo->expects($this->once())->method('addOrUpdatePurchase');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        return $purchaseService->createPurchaseEntityForThirdParty($purchaseProcess);
    }

    /**
     * @test
     * @depends it_should_add_the_process_entity_to_repo_for_third_party
     * @param Purchase $purchaseEntity Purchase entity
     * @return void
     */
    public function it_should_return_a_valid_process_object_for_third_party(Purchase $purchaseEntity): void
    {
        $this->assertInstanceOf(Purchase::class, $purchaseEntity);
    }

    /**
     * @test
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidAmountException
     * @throws ReflectionException
     * @throws \Exception
     */
    public function it_should_not_add_the_purchase_entity_to_repo_for_third_party_if_purchse_status_is_failed()
    {
        $purchaseProcess = $this->createPurchaseProcess();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'declined',
            EpochBiller::BILLER_NAME
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $this->assertEquals(null, $purchaseService->createPurchaseEntityForThirdParty($purchaseProcess));
    }

    /**
     * @test
     * @return void
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \Exception
     */
    public function it_should_add_the_process_entity_to_repo_when_nsf(): void
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();
        $purchaseProcess->resetMemberId();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            true,
            null,
            null,
            null,
            true
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);
        $repo->expects($this->once())->method('add');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(true);


        $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \Exception
     */
    public function it_should_not_add_the_process_entity_to_repo_when_nsf_but_disabled_on_site(): void
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            null,
            null,
            null,
            null,
            true
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);


        $purchaseEntity = $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );

        $this->assertNull($purchaseEntity);
    }

    /**
     * @test
     * @return void
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \Exception
     */
    public function it_should_not_add_the_process_entity_to_repo_when_nsf_but_not_new_cc(): void
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_DECLINED,
            RocketgateBiller::BILLER_NAME,
            false,
            null,
            null,
            null,
            true
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(true);


        $purchaseEntity = $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );

        $this->assertNull($purchaseEntity);
    }

    /**
     * @test
     * @return Purchase
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidAmountException
     * @throws ReflectionException
     */
    public function it_should_call_create_payment_template_if_new_cc_used(): Purchase
    {
        $newCCUsed = true;

        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess(['memberId' => $this->faker->uuid]);
        $purchaseProcess->resetMemberId();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME,
            $newCCUsed
        );

        $purchaseProcess->setPaymentTemplateCollection(null);

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);
        $this->createPaymentTemplateService->expects($this->once())->method('addPaymentTemplate');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);

        return $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );
    }

    /**
     * @test
     * @return Purchase
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws \ProBillerNG\Logger\Exception
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidAmountException
     * @throws ReflectionException
     */
    public function it_should_not_call_create_payment_template_if_new_cc_was_not_used(): Purchase
    {
        $newCCUsed = false;

        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess(['memberId' => $this->faker->uuid]);
        $purchaseProcess->resetMemberId();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME,
            $newCCUsed
        );

        $paymentTemplate = PaymentTemplate::create(
            $this->faker->uuid,
            (string) random_int(400000, 499999),
            '',
            (string) random_int(2021, 2030),
            (string) random_int(01, 12),
            (new \DateTime('now'))->format('Y-m-d H:i:s'),
            (new \DateTime('now'))->format('Y-m-d H:i:s'),
            RocketgateBiller::BILLER_NAME,
            []
        );

        $paymentTemplateCollection = new PaymentTemplateCollection();
        $paymentTemplateCollection->add($paymentTemplate);

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);
        $purchaseProcess->setPaymentTemplateCollection($paymentTemplateCollection);

        $repo = $this->createMock(PurchaseRepository::class);
        $this->createPaymentTemplateService->expects($this->exactly(0))->method('addPaymentTemplate');

        $legacyImportService = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);

        return $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidAmountException
     * @throws PurchaseEntityCannotBeCreatedException
     * @throws ReflectionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_create_async_event_when_sync_payment_template_creation_fails(): void
    {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->createPurchaseProcess(['memberId' => $this->faker->uuid]);
        $purchaseProcess->resetMemberId();
        $purchaseProcess->validate();
        $purchaseProcess->startProcessing();
        $purchaseProcess->finishProcessing();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            'approved',
            RocketgateBiller::BILLER_NAME,
            true
        );

        $purchaseProcess->retrieveMainPurchaseItem()->transactionCollection()->add($transaction);

        $repo = $this->createMock(PurchaseRepository::class);
        $repo->expects($this->once())->method('add');

        $legacyImportService        = $this->createMock(LegacyImportService::class);
        $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
        $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

        $paymentTemplateService = $this->createMock(CreatePaymentTemplateService::class);
        $paymentTemplateService->method('addPaymentTemplate')->willThrowException(new \Exception);
        $paymentTemplateService->expects($this->once())->method('createPaymentTemplateAsyncEvent');

        $purchaseService = new PurchaseService($repo, $legacyImportService, $paymentTemplateService);

        $site = $this->createMock(Site::class);
        $site->method('isNsfSupported')->willReturn(false);
        $purchaseEntity = $purchaseService->createPurchaseEntity(
            $purchaseProcess,
            $site
        );
    }
}
