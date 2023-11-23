<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\TimerPendingPurchases;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
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
     * @throws \Exception
     */
    public function operation_should_update_process_session_when_pending_purchase()
    {
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

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->expects($this->once())->method('update');

        $handler = new TimerPendingPurchasesCommandHandler(
            $this->createMock(Projectionist::class),
            new ItemSourceBuilder(),
            $processHandler,
            $this->createMock(TransactionService::class)
        );

        $handler->operation($eventMock);
    }
}
