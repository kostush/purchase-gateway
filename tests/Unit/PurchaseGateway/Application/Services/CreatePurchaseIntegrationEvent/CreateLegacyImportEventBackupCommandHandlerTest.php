<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\CreateLegacyImportEventBackupCommandHandler;
use Tests\UnitTestCase;

class CreateLegacyImportEventBackupCommandHandlerTest extends UnitTestCase
{
    /**
     * @var MockObject|CreateLegacyImportEventBackupCommandHandler
     */
    private $handler;

    /**
     * Setup method
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getMockBuilder(CreateLegacyImportEventBackupCommandHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'handlePurchase',
                    'retrieveTransactionData',
                    'handleIntegrationEvent',
                    'createPurchaseIntegrationEvent',
                    'publishIntegrationEvent',
                    'bundleRepository',
                    'siteRepository'
                ]
            )
            ->getMock();
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_handle_main_transaction_data(): void
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')->willReturn(
            json_encode(
                $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
                    [
                        'transactionCollection' => [
                            [
                                'state'         => 'approved',
                                'transactionId' => $this->faker->uuid
                            ]
                        ]
                    ]
                )
            )
        );

        $this->handler->expects($this->once())->method('handlePurchase');

        $reflection = new \ReflectionClass(CreateLegacyImportEventBackupCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }
}
