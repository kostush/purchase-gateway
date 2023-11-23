<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\RetrieveBillerTransaction;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\BillerTransactionQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\EpochBillerTransactionQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\HttpQueryDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\RetrieveBillerTransaction\NetbillingBillerTransactionQueryHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidUUIDException;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveBillerTransactionQueryHandler;
use ProBillerNG\PurchaseGateway\Application\Services\RetrieveBillerTransaction\RetrieveItemQuery;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ItemNotFoundException;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use Tests\IntegrationTestCase;

class RetrieveBillerTransactionQueryHandlerTest extends IntegrationTestCase
{
    /**
     * @var RetrieveBillerTransactionQueryHandler
     */
    private $handler;

    /**
     * @var MockObject
     */
    private $repository;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->repository         = $this->createMock(ItemRepositoryReadOnly::class);
        $this->transactionService = $this->createMock(TransactionService::class);
        $this->handler            = new RetrieveBillerTransactionQueryHandler(
            $this->repository,
            $this->transactionService,
            new HttpQueryDTOAssembler()
        );
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_return_dto_instance_given_successful_retrieve(): array
    {
        $item = $this->createItemDatabaseRecord();
        $this->repository->method('findById')->willReturn($item);

        $transaction = $this->createMockedTransaction();
        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);

        $query = new RetrieveItemQuery((string) $item->itemId(), $this->faker->uuid);

        $billerTransaction = $this->handler->execute($query);

        $this->assertInstanceOf(BillerTransactionQueryHttpDTO::class, $billerTransaction);

        return $billerTransaction->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_dto_having_a_transaction_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('transactionId', $billerTransaction);
    }

    /**
     * @test
     * @depends it_should_return_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_dto_having_a_biller_transaction_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerTransactionId', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_dto_having_a_biller_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerId', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_dto_having_a_biller_name(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerName', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_dto_having_biller_fields(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerFields', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_given_non_existing_item_id(): void
    {
        $this->markTestSkipped('When item is not found, purchase gateway retrieve transaction by ItemId.');
        $this->expectException(ItemNotFoundException::class);

        $query = new RetrieveItemQuery($this->faker->uuid, $this->faker->uuid);

        $this->repository->method('findById')
            ->with($query->itemId())
            ->willReturn(null);

        $this->handler->execute($query);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_given_an_invalid_item_id(): void
    {
        $this->expectException(InvalidUUIDException::class);

        $query = new RetrieveItemQuery('aaaaaa', $this->faker->uuid);

        $this->handler->execute($query);
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_return_netbilling_dto_instance_given_successful_retrieve(): array
    {
        $item = $this->createItemRecordForNetbilling();
        $this->repository->method('findById')->willReturn($item);

        $transaction = $this->createNetbillingMockedTransaction();

        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);

        $query = new RetrieveItemQuery((string) $item->itemId(), $this->faker->uuid);

        $billerTransaction = $this->handler->execute($query);

        $this->assertInstanceOf(NetbillingBillerTransactionQueryHttpDTO::class, $billerTransaction);

        return $billerTransaction->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_netbilling_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_netbilling_dto_having_a_currency(array $billerTransaction): void
    {
        $this->assertArrayHasKey('currency', $billerTransaction);
    }

    /**
     * @test
     * @depends it_should_return_netbilling_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return void
     */
    public function it_should_return_netbilling_dto_having_a_site_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('siteId', $billerTransaction);
    }

    /**
     * @test
     * @depends it_should_return_netbilling_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return array
     */
    public function it_should_return_dto_having_netbilling_biller_fields(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerFields', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_netbilling_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller Transaction
     * @return void
     */
    public function it_should_return_netbilling_dto_having_a_account_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('accountId', $billerTransaction['billerTransaction']['billerFields']);
    }

    /**
     * @test
     * @depends it_should_return_netbilling_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller Transaction
     * @return void
     */
    public function it_should_return_netbilling_dto_having_a_site_tag(array $billerTransaction): void
    {
        $this->assertArrayHasKey('siteTag', $billerTransaction['billerTransaction']['billerFields']);
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_return_epoch_dto_instance_given_successful_retrieve(): array
    {
        $item = $this->createItemRecordForEpoch();
        $this->repository->method('findById')->willReturn($item);

        $transaction = $this->createEpochMockedTransaction();

        $this->transactionService->method('getTransactionDataBy')->willReturn($transaction);

        $query = new RetrieveItemQuery((string) $item->itemId(), $this->faker->uuid);

        $billerTransaction = $this->handler->execute($query);

        $this->assertInstanceOf(EpochBillerTransactionQueryHttpDTO::class, $billerTransaction);

        return $billerTransaction->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_epoch_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller transaction
     * @return array
     */
    public function it_should_return_dto_having_epoch_biller_fields(array $billerTransaction): void
    {
        $this->assertArrayHasKey('billerFields', $billerTransaction['billerTransaction']);
    }

    /**
     * @test
     * @depends it_should_return_epoch_dto_instance_given_successful_retrieve
     * @param array $billerTransaction Biller Transaction
     * @return void
     */
    public function it_should_return_epoch_dto_having_a_client_id(array $billerTransaction): void
    {
        $this->assertArrayHasKey('clientId', $billerTransaction['billerTransaction']['billerFields']);
    }
}
