<?php

namespace Tests\Unit\PurchaseGateway\Application\BI\Processed;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\AttemptedTransactions;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class AttemptedTransactionsTest extends UnitTestCase
{
    /**
     * @var TransactionCollection
     */
    private $mainTransactionCollection;

    /**
     * @var InitializedItem
     */
    private $crossSaleItem;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->mainTransactionCollection = new TransactionCollection();

        $mainTransaction = Transaction::create(
            TransactionId::createFromString('707ab722-b397-11e9-a2a3-2a2ae2dbcce4'),
            'approved',
            RocketgateBiller::BILLER_NAME,
            true,
            null,
            null,
            null,
            null
        );
        $this->mainTransactionCollection->add($mainTransaction);

        $crossSaleTransactionCollection = new TransactionCollection();

        $crossSaleTransaction = Transaction::create(
            TransactionId::createFromString('55a4e640-f22f-4397-82c7-13369a24d54b'),
            'approved',
            RocketgateBiller::BILLER_NAME,
            true,
            null,
            null,
            null
        );
        $crossSaleTransactionCollection->add($crossSaleTransaction);
        $this->crossSaleItem = $this->createMock(InitializedItem::class);
        $this->crossSaleItem->method('transactionCollection')->willReturn($crossSaleTransactionCollection);
    }

    /**
     * @test
     * @return array
     */
    public function it_should_return_a_valid_attempted_transactions_object(): array
    {
        $transactionCollection = new TransactionCollection();

        $crossSale = $this->createMock(InitializedItem::class);
        $crossSale->method('transactionCollection')->willReturn($transactionCollection);

        $attemptedTransactions = AttemptedTransactions::create(
            1,
            'rocketgate',
            Transaction::STATUS_APPROVED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            []
        );

        $this->assertInstanceOf(AttemptedTransactions::class, $attemptedTransactions);

        return $attemptedTransactions->toArray();
    }

    /**
     * @test
     * @depends it_should_return_a_valid_attempted_transactions_object
     * @param array $attemptedTransactions Attempted transactions
     * @return void
     */
    public function it_should_contain_the_submit_attempt_key(array $attemptedTransactions): void
    {
        $this->assertSame(1, $attemptedTransactions['submitAttempt']);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_attempted_transactions_object
     * @param array $attemptedTransactions Attempted transactions
     * @return void
     */
    public function it_should_contain_the_biller_name_key(array $attemptedTransactions): void
    {
        $this->assertSame('rocketgate', $attemptedTransactions['billerName']);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_attempted_transactions_object
     * @param array $attemptedTransactions Attempted transactions
     * @return void
     */
    public function it_should_contain_the_success_key(array $attemptedTransactions): void
    {
        $this->assertTrue($attemptedTransactions['success']);
    }

    /**
     * @test
     * @depends it_should_return_a_valid_attempted_transactions_object
     * @param array $attemptedTransactions Attempted transactions
     * @return void
     */
    public function it_should_contain_the_transactions_key(array $attemptedTransactions): void
    {
        $this->assertSame(
            [
                [
                    'transactionId' => '707ab722-b397-11e9-a2a3-2a2ae2dbcce4',
                    'routingCode'   => null,
                    'isCrossSale'   => false,
                    'success'       => true,
                    'isNsf'         => null
                ],
                [
                    'transactionId' => '55a4e640-f22f-4397-82c7-13369a24d54b',
                    'routingCode'   => null,
                    'isCrossSale'   => true,
                    'success'       => true,
                    'isNsf'         => null
                ]
            ],
            $attemptedTransactions['transactions']
        );
    }

    /**
     * @test
     * @return array
     */
    public function it_should_return_an_attempted_transaction_with_success_set_to_false(): array
    {
        $attemptedTransactions = AttemptedTransactions::create(
            0,
            'rocketgate',
            Transaction::STATUS_ABORTED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            null
        );

        $this->assertFalse($attemptedTransactions->toArray()['success']);

        return $attemptedTransactions->toArray();
    }

    /**
     * @test
     * @depends it_should_return_an_attempted_transaction_with_success_set_to_false
     * @param array $attemptedTransactions Attempted transactions
     * @return void
     */
    public function it_should_increment_the_submit_number_by_one_even_if_main_transaction_was_declined_or_abborted(
        array $attemptedTransactions
    ): void {
        $this->assertSame(1, $attemptedTransactions['submitAttempt']);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_add_pending_transaction_to_transaction_collection(): void
    {
        $crossSaleTransactionCollection = new TransactionCollection();

        $crossSaleTransaction = Transaction::create(
            TransactionId::createFromString('55a4e640-f22f-4397-82c7-13369a24d54b'),
            'pending',
            RocketgateBiller::BILLER_NAME,
            true
        );

        $crossSaleTransactionCollection->add($crossSaleTransaction);
        $crossSaleItem = $this->createMock(InitializedItem::class);
        $crossSaleItem->method('transactionCollection')->willReturn($crossSaleTransactionCollection);

        $attemptedTransactions = AttemptedTransactions::create(
            1,
            'rocketgate',
            Transaction::STATUS_APPROVED,
            $this->mainTransactionCollection->toArray(),
            [$crossSaleItem],
            null
        );

        $this->assertSame(
            [
                [
                    'transactionId' => '707ab722-b397-11e9-a2a3-2a2ae2dbcce4',
                    'routingCode'   => null,
                    'isCrossSale'   => false,
                    'success'       => true,
                    'isNsf'         => null
                ]
            ],
            $attemptedTransactions->toArray()['transactions']
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_add_transaction_with_different_biller_name_than_current_biller_to_transaction_collection(
    ): void
    {
        $mainTransactionCollection = new TransactionCollection();

        $rocketgateTransaction = Transaction::create(
            TransactionId::createFromString('707ab722-b397-11e9-a2a3-2a2ae2dbcce4'),
            'aborted',
            RocketgateBiller::BILLER_NAME,
            true
        );
        $netbillingTransaction = Transaction::create(
            TransactionId::createFromString('707ab722-b397-11e9-a2a3-2a2ae2dbcce4'),
            'aborted',
            NetbillingBiller::BILLER_NAME,
            true
        );
        $mainTransactionCollection->add($rocketgateTransaction);
        $mainTransactionCollection->add($netbillingTransaction);

        $attemptedTransactions = AttemptedTransactions::create(
            1,
            'rocketgate',
            Transaction::STATUS_APPROVED,
            $mainTransactionCollection->toArray(),
            [],
            null
        );

        $this->assertSame(
            [
                [
                    'transactionId' => '707ab722-b397-11e9-a2a3-2a2ae2dbcce4',
                    'routingCode'   => null,
                    'isCrossSale'   => false,
                    'success'       => false,
                    'isNsf'         => null
                ]
            ],
            $attemptedTransactions->toArray()['transactions']
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_payment_template_used_true(): void
    {
        $attemptedTransactions = AttemptedTransactions::create(
            0,
            'rocketgate',
            Transaction::STATUS_ABORTED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            [
                "cardHash"   => $_ENV['NETBILLING_CARD_HASH'],
                "binRouting" => ""
            ]
        );

        $this->assertTrue($attemptedTransactions->toArray()['existingPaymentTemplateUsed']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_payment_template_used_false(): void
    {
        $attemptedTransactions = AttemptedTransactions::create(
            0,
            'rocketgate',
            Transaction::STATUS_ABORTED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            null
        );

        $this->assertFalse($attemptedTransactions->toArray()['existingPaymentTemplateUsed']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_default_biller_as_a_false(): void
    {
        $attemptedTransactions = AttemptedTransactions::create(
            0,
            'rocketgate',
            Transaction::STATUS_ABORTED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            null
        );

        $this->assertFalse($attemptedTransactions->toArray()['defaultBiller']);
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_default_biller_as_a_true(): void
    {
        $attemptedTransactions = AttemptedTransactions::create(
            1,
            'rocketgate',
            Transaction::STATUS_ABORTED,
            $this->mainTransactionCollection->toArray(),
            [$this->crossSaleItem],
            null
        );

        $this->assertTrue($attemptedTransactions->toArray()['defaultBiller']);
    }
}
