<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\BillerNotSupportedException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidThreeDVersionException;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use Tests\UnitTestCase;

class TransactionTest extends UnitTestCase
{
    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_transaction_object(): Transaction
    {
        $result = Transaction::create(
            TransactionId::createFromString($this->faker->uuid),
            Transaction::STATUS_APPROVED,
            RocketgateBiller::BILLER_NAME,
            true
        );

        self::assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_create_a_transaction_object_for_qysso_biller(): void
    {
        $transaction = Transaction::create(
            TransactionId::create(),
            Transaction::STATUS_APPROVED,
            QyssoBiller::BILLER_NAME,
            true
        );

        self::assertInstanceOf(Transaction::class, $transaction);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_create_a_transaction_object_for_epoch_biller(): void
    {
        $transaction = Transaction::create(
            TransactionId::create(),
            Transaction::STATUS_APPROVED,
            EpochBiller::BILLER_NAME,
            true
        );

        self::assertInstanceOf(Transaction::class, $transaction);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function it_should_create_a_transaction_object_for_netbilling_biller(): void
    {
        $transaction = Transaction::create(
            TransactionId::create(),
            Transaction::STATUS_APPROVED,
            NetbillingBiller::BILLER_NAME,
            true
        );

        self::assertInstanceOf(Transaction::class, $transaction);
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_transaction_id_object(Transaction $transaction): void
    {
        $this->assertInstanceOf(TransactionId::class, $transaction->transactionId());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_the_correct_state(Transaction $transaction): void
    {
        $this->assertEquals('approved', $transaction->state());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_default_biller_name(Transaction $transaction): void
    {
        $this->assertEquals(RocketgateBiller::BILLER_NAME, $transaction->billerName());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_new_cc_used_flag(Transaction $transaction): void
    {
        $this->assertTrue($transaction->newCCUsed());
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_an_exception_if_a_unknown_biller_name_is_used(): void
    {
        $this->expectException(BillerNotSupportedException::class);

        Transaction::create(
            TransactionId::create(),
            Transaction::STATUS_ABORTED,
            'unknownBiller',
            true
        );
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_acs_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->acs());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_pareq_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->pareq());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_redirect_url_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->redirectUrl());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_is_nsf_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->isNsf());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_device_collection_url_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->deviceCollectionUrl());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_device_collection_jwt_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->deviceCollectionJwt());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_device_fingerprint_id_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->deviceFingerprintId());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_treeD_step_up_url_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->threeDStepUpUrl());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_contain_a_null_treeD_version_as_default(Transaction $transaction): void
    {
        $this->assertNull($transaction->threeDVersion());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_set_device_fingerprint_id(Transaction $transaction): void
    {
        $transaction->setDeviceFingerprintId('device-fingerprint-id');

        $this->assertEquals('device-fingerprint-id', $transaction->deviceFingerprintId());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     */
    public function it_should_set_threeD_step_up_url(Transaction $transaction): void
    {
        $transaction->setThreeDStepUpUrl('threeD_step_up_url');

        $this->assertEquals('threeD_step_up_url', $transaction->threeDStepUpUrl());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     * @throws Exception
     * @throws InvalidThreeDVersionException
     */
    public function it_should_set_threeD_version(Transaction $transaction): void
    {
        $transaction->setThreeDVersion(2);

        $this->assertEquals(2, $transaction->threeDVersion());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_object
     * @param Transaction $transaction Transaction
     * @return void
     * @throws Exception
     * @throws InvalidThreeDVersionException
     */
    public function it_should_throw_invalid_three_d_version_exception(Transaction $transaction): void
    {
        $this->expectException(InvalidThreeDVersionException::class);

        $transaction->setThreeDVersion(3);
    }
}
