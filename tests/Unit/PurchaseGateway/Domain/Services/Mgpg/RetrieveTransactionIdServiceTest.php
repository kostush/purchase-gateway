<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Services\Mgpg;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\Mgpg\RetrieveTransactionIdService;
use Tests\UnitTestCase;

class RetrieveTransactionIdServiceTest extends UnitTestCase
{
    /**
     * @test
     * @throws Exception
     */
    public function it_should_return_item_id_as_transaction_id_when_item_not_found_on_database(): void
    {
        //given
        $itemId         = $this->faker->uuid;
        $itemRepository = $this->createMock(ItemRepositoryReadOnly::class);
        $itemRepository->method('findById')->willReturn(null);

        //when
        $service       = new RetrieveTransactionIdService($itemRepository);
        $transactionId = $service->findByItemIdOrReturnItemId($itemId);

        //then
        $this->assertEquals($itemId, $transactionId);
    }

    /**
     * @test
     * @throws Exception
     * @throws \Exception
     */
    public function it_should_return_transaction_id_when_item_found_on_database(): void
    {
        //given
        $itemId            = $this->faker->uuid;
        $itemTransactionId = '415f9713-796b-4286-bb68-490b5577bbb0';
        $item              = $this->createMock(ProcessedBundleItem::class);
        $item->method('retrieveTransactionId')
            ->willReturn(TransactionId::createFromString($itemTransactionId));

        //when
        $itemRepository = $this->createMock(ItemRepositoryReadOnly::class);
        $itemRepository->method('findById')->willReturn($item);

        $service                = new RetrieveTransactionIdService($itemRepository);
        $retrievedTransactionId = $service->findByItemIdOrReturnItemId($itemId);

        //then
        $this->assertEquals($itemTransactionId, $retrievedTransactionId);
    }
}
