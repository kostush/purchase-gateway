<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCheckRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use Tests\UnitTestCase;

class RocketgateCheckRetrieveTransactionResultTest extends UnitTestCase
{
    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $invoiceId;

    /**
     * @var string
     */
    private $customerId;

    /**
     * @var string
     */
    private $siteId;

    /**
     * @var string
     */
    private $billerId;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * Init
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->merchantId    = '6b4d4432-743a-485e-9373-fe318f72e3f3';
        $this->invoiceId     = '4caac2be-c3f0-4fc2-a017-15f6157cb558';
        $this->customerId    = '113f545f-7b76-41df-b0d1-d7ff053247cd';
        $this->siteId        = '3f26bc1b-6dcc-4de8-96f1-30e4218eedb8';
        $this->transactionId = 'fa6bce9d-7513-4640-9536-757d41c56255';
        $this->billerId      = '966a0200-58c9-4f23-80be-de534948ad52';
    }

    /**
     * @test
     * @return RocketgateCheckRetrieveTransactionResult
     */
    public function it_should_return_a_rocketgate_check_retrieve_transaction_result_object_if_correct_data_is_sent()
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn($this->billerId);
        $retrieveTransaction->method('getTransactionId')->willReturn($this->transactionId);
        $retrieveTransaction->method('getSiteId')->willReturn($this->siteId);
        $retrieveTransaction->method('getMerchantId')->willReturn($this->merchantId);
        $retrieveTransaction->method('getInvoiceId')->willReturn($this->invoiceId);
        $retrieveTransaction->method('getCustomerId')->willReturn($this->customerId);
        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn(ChequePaymentInfo::PAYMENT_TYPE);
        $retrieveTransaction->method('getMerchantPassword')->willReturn('password');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getMerchantAccount')->willReturn('MerchantAccount');
        $retrieveTransaction->method('getCardDescription')->willReturn('CREDIT');
        $retrieveTransaction->method('getBillerTransactions')->willReturn([]);
        $retrieveTransaction->method('getSecuredWithThreeD')->willReturn(false);

        $memberInformation = $this->createMock(MemberInformation::class);

        $checkTransactionInformation = $this->createMock(CheckTransactionInformation::class);

        $billerFields = RocketgateBillerFields::create(
            $this->merchantId,
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
            $this->faker->uuid,
            'sharedSecret',
            true
        );

        $rocketgateCheckRetrieveTransactionResult = new RocketgateCheckRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $checkTransactionInformation,
            $billerFields
        );

        self::assertInstanceOf(
            RocketgateCheckRetrieveTransactionResult::class,
            $rocketgateCheckRetrieveTransactionResult
        );

        return $rocketgateCheckRetrieveTransactionResult;
    }

    /**
     * @test
     *
     * @param RocketgateCheckRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCheckRetrieveTransactionResult
     *
     * @depends it_should_return_a_rocketgate_check_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_all_data_in_array_format(
        RocketgateCheckRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $arrayResult = $rocketgateCCRetrieveTransactionResult->toArray();

        $this->assertEquals($this->billerId, $arrayResult['billerId']);
        $this->assertEquals($this->transactionId, $arrayResult['transactionId']);
        $this->assertEquals($this->siteId, $arrayResult['siteId']);
        $this->assertEquals($this->merchantId, $arrayResult['billerFields']['merchantId']);
        $this->assertEquals($_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'], $arrayResult['billerFields']['merchantPassword']);
        $this->assertEquals('RON', $arrayResult['currency']);
        $this->assertEquals(ChequePaymentInfo::PAYMENT_TYPE, $arrayResult['paymentType']);
        $this->assertEquals([], $arrayResult['transactionInformation']['CCTransactionInformation']);
    }
}