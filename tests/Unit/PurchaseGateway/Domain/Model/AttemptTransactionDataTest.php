<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\AttemptTransactionData;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use Tests\UnitTestCase;

class AttemptTransactionDataTest extends UnitTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CurrencyCode
     */
    private $currency;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|UserInfo
     */
    private $userInfo;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|CCPaymentInfo
     */
    private $paymentInfo;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->currency    = $this->createMock(CurrencyCode::class);
        $this->userInfo    = $this->createMock(UserInfo::class);
        $this->paymentInfo = $this->createMock(CCPaymentInfo::class);
    }

    /**
     * @test
     * @return AttemptTransactionData
     */
    public function it_should_create_a_attempt_transaction_data(): AttemptTransactionData
    {
        $attemptTransactionData = AttemptTransactionData::create(
            $this->currency,
            $this->userInfo,
            $this->paymentInfo
        );

        $this->assertInstanceOf(AttemptTransactionData::class, $attemptTransactionData);

        return $attemptTransactionData;
    }

    /**
     * @test
     * @depends it_should_create_a_attempt_transaction_data
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @return void
     */
    public function it_should_contain_correct_currency(
        AttemptTransactionData $attemptTransactionData
    ): void {
        $this->assertEquals($this->currency, $attemptTransactionData->currency());
    }

    /**
     * @test
     * @depends it_should_create_a_attempt_transaction_data
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @return void
     */
    public function it_should_contain_correct_user_info(
        AttemptTransactionData $attemptTransactionData
    ): void {
        $this->assertEquals($this->userInfo, $attemptTransactionData->userInfo());
    }

    /**
     * @test
     * @depends it_should_create_a_attempt_transaction_data
     * @param AttemptTransactionData $attemptTransactionData Attempt transaction data
     * @return void
     */
    public function it_should_contain_correct_payment_info(
        AttemptTransactionData $attemptTransactionData
    ): void {
        $this->assertEquals($this->paymentInfo, $attemptTransactionData->paymentInfo());
    }
}
