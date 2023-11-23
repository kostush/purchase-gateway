<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\TransactionService;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\TokenGenerator;
use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRoutingCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;

use Tests\UnitTestCase;

class AttemptTransactionsWithBinRoutingTest extends UnitTestCase
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
    private $cryptService;

    /**
     * @var BinRouting
     */
    private $binRouting;

    /**
     * @return void
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->binRouting = BinRouting::create(1, '1', 'test');

        $firstTransaction = $this->createMock(Transaction::class);
        $firstTransaction->method('successfulBinRouting')->willReturn(null);
        $secondTransaction = $this->createMock(Transaction::class);
        $secondTransaction->method('successfulBinRouting')->willReturn($this->binRouting);

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

        $crossSaleItem = $this->createMock(InitializedItem::class);
        $crossSaleItem->method('isSelectedCrossSale')->willReturn(true);

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->purchaseProcess->method('retrieveMainPurchaseItem')->willReturn($mainItem);
        $this->purchaseProcess->method('retrieveInitializedCrossSales')->willReturn([$crossSaleItem]);

        $this->transactionTranslatingService = $this->createMock(TransactionTranslatingService::class);
        $this->tokenGenerator                = $this->createMock(TokenGenerator::class);
        $this->cryptService                  = $this->createMock(CryptService::class);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function it_should_attempt_cross_sales_using_the_bin_routing_from_the_successful_transaction_of_the_main_purchase(): void
    {
        /** @var TransactionService | MockObject $transactionService */
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

        $billerMappingMock      = $this->createMock(BillerMapping::class);
        $attemptTransactionData = $this->createMock(AttemptTransactionData::class);

        $transactionService->expects($this->once())
            ->method('attemptCrossSaleTransactions')
            ->with(
                $this->purchaseProcess->retrieveMainPurchaseItem(),
                $this->purchaseProcess->retrieveProcessedCrossSales(),
                $billerMappingMock,
                $this->createMock(RocketgateBiller::class),
                $attemptTransactionData
            );

        $binRoutingCollection = new BinRoutingCollection();
        $binRoutingCollection->add($this->createMock(BinRouting::class));

        $transactionService->attemptTransactions(
            $this->purchaseProcess->retrieveMainPurchaseItem(),
            $this->purchaseProcess->retrieveProcessedCrossSales(),
            $billerMappingMock,
            $binRoutingCollection,
            $this->createMock(RocketgateBiller::class),
            $attemptTransactionData,
            $this->createMock(FraudAdvice::class),
            $this->createMock(Site::class)
        );
    }
}
