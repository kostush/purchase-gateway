<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Exceptions\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;

use ReflectionClass;
use Tests\UnitTestCase;

class TransactionServiceTest extends UnitTestCase
{
    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var TransactionTranslatingService
     */
    private $transactionTranslatingService;

    /**
     * @var TokenGenerator
     */
    private $tokenGenerator;

    /**
     * @var CryptService
     */
    protected $cryptService;

    /**
     * @var BinRoutingCollection
     */
    private $binRoutingCollection;

    /**
     * @var MockObject|BillerMapping
     */
    private $billerMapping;

    /**
     * @var MockObject|AttemptTransactionData
     */
    private $attemptTransactionData;

    /**
     * @var string
     */
    private $mainItemId = '8570afd8-c52e-446b-8ee6-e3be47e322fc';

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $firstTransaction = $this->createMock(Transaction::class);
        $firstTransaction->method('successfulBinRouting')->willReturn(null);
        $secondTransaction = $this->createMock(Transaction::class);
        $secondTransaction->method('successfulBinRouting')->willReturn(null);

        $transactionsForMainItem = new TransactionCollection(
            [
                $firstTransaction,
                $secondTransaction
            ]
        );

        $mainItem = $this->createMock(InitializedItem::class);
        $mainItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $mainItem->method('transactionCollection')->willReturn($transactionsForMainItem);
        $mainItem->method('lastTransactionId')->willReturn(TransactionId::create());
        $mainItem->method('itemId')->willReturn(ItemId::createFromString($this->mainItemId));

        $crossSaleItem = $this->createMock(InitializedItem::class);
        $crossSaleItem->method('isSelectedCrossSale')->willReturn(true);

        $this->binRoutingCollection = new BinRoutingCollection();
        $this->binRoutingCollection->add($this->createMock(BinRouting::class));
        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($mainItem);
        $this->purchaseProcess->method('retrieveInitializedCrossSales')->willReturn([$crossSaleItem]);

        $this->billerMapping          = $this->createMock(BillerMapping::class);
        $this->attemptTransactionData = $this->createMock(AttemptTransactionData::class);

        $this->transactionTranslatingService = $this->createMock(TransactionTranslatingService::class);
        $this->tokenGenerator                = $this->createMock(TokenGenerator::class);
        $this->cryptService                  = $this->createMock(CryptService::class);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_call_attempt_transaction_with_bin_routing(): void
    {
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService
                ]
            )
            ->onlyMethods(['attemptTransactionWithBinRouting'])
            ->getMock();

        $transactionService->expects($this->exactly(2))->method('attemptTransactionWithBinRouting');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $this->createMock(Site::class)
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_call_attempt_main_transaction(): void
    {
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService,
                ]
            )
            ->onlyMethods(['attemptMainTransaction'])
            ->getMock();

        $transactionService->expects($this->once())->method('attemptMainTransaction');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $this->createMock(Site::class)
        );
    }

    /**
     * @test
     * @return void
     */
    public function attempt_transactions_should_call_attempt_cross_sale_transactions(): void
    {
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService,
                ]
            )
            ->onlyMethods(['attemptCrossSaleTransactions'])
            ->getMock();

        $transactionService->expects($this->once())->method('attemptCrossSaleTransactions');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $this->createMock(Site::class)
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function attempt_transactions_should_call_perform_transaction(): void
    {
        /** @var TransactionService $transactionService */
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService,
                ]
            )
            ->onlyMethods(['performTransaction'])
            ->getMock();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $transactionService->expects($this->exactly(2))->method('performTransaction');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     */
    public function attempt_transactions_should_call_perform_transaction_with_new_card(): void
    {
        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService,
                ]
            )
            ->onlyMethods(['performTransactionWithNewCard'])
            ->getMock();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $transactionService->expects($this->exactly(2))->method('performTransactionWithNewCard');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     */
    public function attempt_transactions_should_call_perform_transaction_with_existing_card(): void
    {
        $this->attemptTransactionData->method('paymentInfo')->willReturn(
            $this->createMock(ExistingCCPaymentInfo::class)
        );

        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService,
                ]
            )
            ->onlyMethods(['performTransactionWithExistingCard'])
            ->getMock();

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $transactionService->expects($this->exactly(2))->method('performTransactionWithExistingCard');
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_call_the_transaction_translating_service_new_card(): void
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->transactionTranslatingService->expects($this->exactly(2))->method('performTransactionWithNewCard');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_call_the_transaction_translating_service_existing_card(): void
    {
        $this->attemptTransactionData->method('paymentInfo')->willReturn(
            $this->createMock(ExistingCCPaymentInfo::class)
        );

        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->transactionTranslatingService->expects($this->exactly(2))->method('performTransactionWithExistingCard');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_retrieve_the_user_info_from_the_attempt_transaction_data(): void
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->attemptTransactionData->expects($this->exactly(2))->method('userInfo');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function attempt_transactions_should_retrieve_the_payment_info_from_the_attempt_transaction_data(): void
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->attemptTransactionData->expects($this->exactly(6))->method('paymentInfo');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveInitializedCrossSales(),
            $this->billerMapping,
            $this->binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function get_transaction_data_by_id_should_call_transaction_translation_service(): void
    {
        $this->transactionTranslatingService->expects($this->once())->method('getTransactionDataBy');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->getTransactionDataBy(TransactionId::create(), SessionId::create());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function should_use_three_d_method_should_return_true_when_three_d_is_supported_and_force_flag_on_fraud_advice_is_true(): void
    {
        $service = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $biller = $this->createMock(RocketgateBiller::class);
        $biller->method('isThreeDSupported')->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isForceThreeD')->willReturn(true);

        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('isCrossSale')->willReturn(false);

        $reflection = new ReflectionClass(TransactionService::class);

        $shouldUseThreeDMethod = $reflection->getMethod('shouldUseThreeD');
        $shouldUseThreeDMethod->setAccessible(true);

        $result = $shouldUseThreeDMethod->invoke($service, $biller, $fraudAdvice, $initializedItem);

        $this->assertTrue($result);
    }

    /**
     * @test
     * @testdox shouldUseThreeD should return false when biller does not support 3D or Force3DS flag on fraud advice is false
     * @return void
     * @throws \Exception
     */
    public function should_use_three_d_method_should_return_false_when_three_d_is_supported_or_force_flag_on_fraud_advice_are_false(): void
    {
        $service = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $biller = $this->createMock(RocketgateBiller::class);
        $biller->method('isThreeDSupported')->willReturn(false);

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isForceThreeD')->willReturn(true);

        $reflection = new ReflectionClass(TransactionService::class);

        $shouldUseThreeDMethod = $reflection->getMethod('shouldUseThreeD');
        $shouldUseThreeDMethod->setAccessible(true);

        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('isCrossSale')->willReturn(false);

        $result = $shouldUseThreeDMethod->invoke($service, $biller, $fraudAdvice, $initializedItem);

        $this->assertFalse($result);
    }

    /**
     * @test
     * @testdox shouldUseThreeD should return false when biller does not support 3D or Force3DS flag on fraud advice is false
     * @return void
     * @throws \Exception
     */
    public function should_use_three_d_method_should_return_false_when_a_cross_sale_is_processed(): void
    {
        $service = $this->getMockBuilder(TransactionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $biller = $this->createMock(RocketgateBiller::class);
        $biller->method('isThreeDSupported')->willReturn(true);

        $fraudAdvice = $this->createMock(FraudAdvice::class);
        $fraudAdvice->method('isForceThreeD')->willReturn(true);

        $reflection = new ReflectionClass(TransactionService::class);

        $shouldUseThreeDMethod = $reflection->getMethod('shouldUseThreeD');
        $shouldUseThreeDMethod->setAccessible(true);

        $initializedItem = $this->createMock(InitializedItem::class);
        $initializedItem->method('isCrossSale')->willReturn(true);

        $result = $shouldUseThreeDMethod->invoke($service, $biller, $fraudAdvice, $initializedItem);

        $this->assertFalse($result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function perform_complete_threeD_should_call_transaction_translation_service(): void
    {
        $this->transactionTranslatingService->expects($this->once())->method('performCompleteThreeDTransaction');
        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );
        $transactionService->performCompleteThreeDTransaction(
            TransactionId::create(),
            'SimulatedPARES10001000E00B000',
            null,
            SessionId::create()
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_call_perform_transaction_once_when_multiple_bin_routings_and_transaction_is_pending(): void
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();

        $site->method('attempts')->willReturn(
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $binRoutingCollection = new BinRoutingCollection();
        $binRoutingCollection->offsetSet(
            $this->mainItemId . '_1',
            $this->createMock(BinRouting::class)
        );
        $binRoutingCollection->offsetSet(
            $this->mainItemId . '_2',
            $this->createMock(BinRouting::class)
        );

        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->transactionTranslatingService,
                    $this->tokenGenerator,
                    $this->cryptService
                ]
            )
            ->onlyMethods(['performTransaction'])
            ->getMock();

        $transaction = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_PENDING,
            RocketgateBiller::BILLER_NAME
        );

        $transactionService->method('performTransaction')->willReturn($transaction);

        $transactionService->expects($this->exactly(1))->method('performTransaction');

        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            [],
            $this->billerMapping,
            $binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $site
        );
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_throw_unknown_biller_when_add_biller_interaction_is_called_with_an_unsupported_biller(): void
    {
        $this->expectException(UnknownBillerNameException::class);

        $transactionService = new TransactionService(
            $this->transactionTranslatingService,
            $this->tokenGenerator,
            $this->cryptService
        );

        $transactionService->addBillerInteraction(
            TransactionId::create(),
            'unknown-biller-name',
            SessionId::create(),
            []
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_not_add_bin_routing_element_to_the_collection_if_null_to_avoid_error_call_function_toArray_on_null()
    {
        $firstTransaction = $this->createMock(Transaction::class);
        $firstTransaction->method('successfulBinRouting')->willReturn(null);
        $secondTransaction = $this->createMock(Transaction::class);
        $secondTransaction->method('successfulBinRouting')->willReturn(null);

        $transactionsForMainItem = new TransactionCollection(
            [
                $firstTransaction,
                $secondTransaction
            ]
        );

        $mainItem = $this->createMock(InitializedItem::class);
        $mainItem->method('wasItemPurchaseSuccessful')->willReturn(true);
        $mainItem->method('transactionCollection')->willReturn($transactionsForMainItem);
        $mainItem->method('lastTransactionId')->willReturn(TransactionId::create());
        $mainItem->method('itemId')->willReturn(ItemId::createFromString($this->mainItemId));

        $crossSaleItem = $this->createMock(InitializedItem::class);
        $crossSaleItem->method('isSelectedCrossSale')->willReturn(true);

        $binRoutingCollection = new BinRoutingCollection();
        $binRoutingCollection->add($this->createMock(BinRouting::class));

        // create purchase with main and xSale items
        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($mainItem);
        $purchaseProcess->method('retrieveInitializedCrossSales')->willReturn([$crossSaleItem]);

        $transactionService = $this->getMockBuilder(TransactionService::class)
            ->setConstructorArgs(
                [
                    $this->createMock(TransactionTranslatingService::class),
                    $this->createMock(TokenGenerator::class),
                    $this->createMock(CryptService::class)
                ]
            )
            ->onlyMethods(['attemptTransactionWithBinRouting'])
            ->getMock();

        $transactionService->expects($this->exactly(2))->method('attemptTransactionWithBinRouting');

        $transactionService->attemptTransactions(
            $purchaseProcess->retrieveMainPurchaseItem(),
            $purchaseProcess->retrieveInitializedCrossSales(),
            $this->createMock(BillerMapping::class),
            $binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $this->createMock(AttemptTransactionData::class),
            $this->createMock(FraudAdvice::class),
            $this->createMock(Site::class)
        );
    }
}
