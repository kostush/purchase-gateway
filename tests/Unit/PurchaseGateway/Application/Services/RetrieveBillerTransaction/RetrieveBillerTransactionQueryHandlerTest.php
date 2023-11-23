<?php

namespace Tests\Unit\PurchaseGateway\Application\Services\RetrieveBillerTransaction;

use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\HttpQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\RocketgateBillerTransactionQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveBillerTransactionQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveItemQuery;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use stdClass;
use Tests\UnitTestCase;

class RetrieveBillerTransactionQueryHandlerTest extends UnitTestCase
{
    /**
     * @var ItemRepositoryReadOnly
     */
    private $repository;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var RetrieveBillerTransactionQueryHandler
     */
    private $handler;

    /**
     * @var ProcessedBundleItem
     */
    private $item;

    /**
     * @var RetrieveItemQuery
     */
    private $query;

    /**
     * @var HttpQueryDTOAssembler
     */
    private $assembler;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository         = $this->createMock(ItemRepositoryReadOnly::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->assembler          = $this->createMock(HttpQueryDTOAssembler::class);
        $this->handler            = new RetrieveBillerTransactionQueryHandler(
            $this->repository,
            $this->transactionService,
            $this->assembler
        );

        $this->item  = $this->createMock(ProcessedBundleItem::class);
        $this->query = $this->createMock(RetrieveItemQuery::class);
        $dto         = $this->createMock(RocketgateBillerTransactionQueryHttpDTO::class);
        $this->assembler->method('assemble')->willReturn($dto);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_call_find_by_id_repository_method_when_an_item_id_is_provided(): void
    {
        $this->query->method('itemId')->willReturn($this->faker->uuid);
        $this->query->method('sessionId')->willReturn($this->faker->uuid);
        $this->item->method('retrieveTransactionId')->willReturn(TransactionId::create());
        $this->transactionService->method('getTransactionDataBy')
            ->willReturn($this->createMock(RocketgateCCRetrieveTransactionResult::class));

        $this->repository->expects($this->once())->method('findById')->willReturn($this->item);

        $this->handler->execute($this->query);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_throw_an_exception_when_the_item_is_not_found(): void
    {
        $this->markTestSkipped('When item is not found, purchase gateway retrieve transaction by ItemId.');
        $this->expectException(ItemNotFoundException::class);

        $this->repository->method('findById')->willReturn(null);
        $this->query->method('itemId')->willReturn($this->faker->uuid);

        $this->handler->execute($this->query);
    }

    /**
     * @test
     * @return void
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_a_invalid_query_is_provided(): void
    {
        $this->expectException(\TypeError::class);

        $this->handler->execute(new stdClass());
    }
}
