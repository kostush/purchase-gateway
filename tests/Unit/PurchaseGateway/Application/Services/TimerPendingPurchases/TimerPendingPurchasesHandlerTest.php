<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\TimerPendingPurchases;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases\TimerPendingPurchasesCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Pending;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use Tests\UnitTestCase;

class TimerPendingPurchasesHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_process_pending_cross_sales_on_processed_session()
    {
        $handler = $this->getMockBuilder(TimerPendingPurchasesCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $purchaseData = $this->createSessionInfo(
            [
                'status'                         => 'processed',
                'transactionCollection'          => [
                    [
                        'state'               => Transaction::STATUS_APPROVED,
                        'transactionId'       => $this->faker->uuid,
                        'billerName'          => EpochBiller::BILLER_NAME,
                        'newCCUsed'           => true,
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => $this->faker->url,
                        'isNsf'               => false,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ],
                'transactionCollectionCrossSale' => [
                    [
                        'state'               => Transaction::STATUS_PENDING,
                        'transactionId'       => $this->faker->uuid,
                        'billerName'          => EpochBiller::BILLER_NAME,
                        'newCCUsed'           => true,
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => $this->faker->url,
                        'isNsf'               => false,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ]
            ]
        );

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode($purchaseData)
            );

        $reflection = new \ReflectionClass(TimerPendingPurchasesCommandHandler::class);
        $method     = $reflection->getMethod('operation');

        $property = $reflection->getProperty('purchaseProcessHandler');
        $property->setAccessible(true);
        $property->setValue($handler, $this->createMock(PurchaseProcessHandler::class));

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->expects($this->once())->method('abortTransaction');

        $property = $reflection->getProperty('transactionService');
        $property->setAccessible(true);
        $property->setValue($handler, $transactionService);

        $method->invoke($handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_handle_main_transaction_when_pending_purchase()
    {
        $handler = $this->getMockBuilder(TimerPendingPurchasesCommandHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'handleTransaction',
                ]
            )
            ->getMock();

        $purchaseData = $this->createSessionInfo(
            [
                'status'                         => Pending::name(),
                'transactionCollection'          => [
                    [
                        'state'               => Transaction::STATUS_PENDING,
                        'transactionId'       => $this->faker->uuid,
                        'billerName'          => EpochBiller::BILLER_NAME,
                        'newCCUsed'           => true,
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => $this->faker->url,
                        'isNsf'               => false,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ],
                'transactionCollectionCrossSale' => [
                    [
                        'state'               => Transaction::STATUS_PENDING,
                        'transactionId'       => $this->faker->uuid,
                        'billerName'          => EpochBiller::BILLER_NAME,
                        'newCCUsed'           => true,
                        'acs'                 => null,
                        'pareq'               => null,
                        'redirectUrl'         => $this->faker->url,
                        'isNsf'               => false,
                        'deviceCollectionUrl' => null,
                        'deviceCollectionJwt' => null,
                        'deviceFingerprintId' => null,
                        'threeDStepUpUrl'     => null,
                        'threeDStepUpJwt'     => null,
                        'md'                  => null,
                        'threeDFrictionless'  => false,
                        'threeDVersion'       => null
                    ]
                ]
            ]
        );

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseData
                )
            );

        $handler->expects($this->exactly(2))->method('handleTransaction');

        $reflection = new \ReflectionClass(TimerPendingPurchasesCommandHandler::class);
        $method     = $reflection->getMethod('operation');

        $property = $reflection->getProperty('purchaseProcessHandler');
        $property->setAccessible(true);
        $property->setValue($handler, $this->createMock(PurchaseProcessHandler::class));

        $method->invoke($handler, $eventMock);
    }
}
