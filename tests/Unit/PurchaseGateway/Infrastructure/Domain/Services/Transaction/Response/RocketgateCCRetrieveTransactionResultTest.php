<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTime;
use DateTimeInterface;
use Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionTransaction;
use Tests\UnitTestCase;

class RocketgateCCRetrieveTransactionResultTest extends UnitTestCase
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
        $this->billerId      = RocketgateBiller::BILLER_ID;
    }

    /**
     * @test
     * @return RocketgateCCRetrieveTransactionResult
     * @throws Exception
     */
    public function it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent()
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn($this->billerId);
        $retrieveTransaction->method('getTransactionId')->willReturn($this->transactionId);
        $retrieveTransaction->method('getSiteId')->willReturn($this->siteId);
        $retrieveTransaction->method('getMerchantId')->willReturn($this->merchantId);
        $retrieveTransaction->method('getInvoiceId')->willReturn($this->invoiceId);
        $retrieveTransaction->method('getCustomerId')->willReturn($this->customerId);
        $retrieveTransaction->method('getCurrency')->willReturn('RON');
        $retrieveTransaction->method('getPaymentType')->willReturn('cc');
        $retrieveTransaction->method('getMerchantPassword')->willReturn('password');
        $retrieveTransaction->method('getCardHash')->willReturn('123456789');
        $retrieveTransaction->method('getMerchantAccount')->willReturn('MerchantAccount');
        $retrieveTransaction->method('getCardDescription')->willReturn('CREDIT');
        $retrieveTransaction->method('getBillerTransactions')->willReturn([]);
        $retrieveTransaction->method('getSecuredWithThreeD')->willReturn(false);

        $transactionMock = $this->createMock(RetrieveTransactionTransaction::class);
        $transactionMock->method('getTransactionId')->willReturn($this->transactionId);
        $transactionMock->method('getAmount')->willReturn('29.99');
        $transactionMock->method('getStatus')->willReturn('approved');
        $transactionMock->method('getCreatedAt')->willReturn((new DateTime())->format(DateTimeInterface::ATOM));
        $transactionMock->method('getRebillAmount')->willReturn('12');
        $transactionMock->method('getRebillStart')->willReturn('1');
        $transactionMock->method('getFirst6')->willReturn('123456');
        $transactionMock->method('getLast4')->willReturn('4444');

        $retrieveTransaction->method('getTransaction')->willReturn($transactionMock);
        $retrieveTransaction->method('getBillerId')->willReturn($this->billerId);

        $ccTransactionInformation = new NewCCTransactionInformation($retrieveTransaction);

        $memberInformation = $this->createMock(MemberInformation::class);

        $retrieveTransaction->method('getTransaction')->willReturn($ccTransactionInformation);
        $billerFields = RocketgateBillerFields::create(
            $this->merchantId,
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'],
            $this->faker->uuid,
            'sharedSecret',
            true
        );

        $rocketgateCCRetrieveTransactionResult = new RocketgateCCRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            $billerFields
        );

        self::assertInstanceOf(RocketgateCCRetrieveTransactionResult::class, $rocketgateCCRetrieveTransactionResult);

        return $rocketgateCCRetrieveTransactionResult;
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_id(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals($this->merchantId, $rocketgateCCRetrieveTransactionResult->merchantId());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_password(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals('password', $rocketgateCCRetrieveTransactionResult->merchantPassword());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_invoice_id(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals($this->invoiceId, $rocketgateCCRetrieveTransactionResult->invoiceId());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_customer_id(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals($this->customerId, $rocketgateCCRetrieveTransactionResult->customerId());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_card_hash(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals('123456789', $rocketgateCCRetrieveTransactionResult->cardHash());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_merchant_account(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals('MerchantAccount', $rocketgateCCRetrieveTransactionResult->merchantAccount());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_card_description(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertEquals('CREDIT', $rocketgateCCRetrieveTransactionResult->cardDescription());
    }

    /**
     * @test
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_biller_transactions_collection(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $this->assertInstanceOf(BillerTransactionCollection::class, $rocketgateCCRetrieveTransactionResult->billerTransactions());
    }

    /**
     * @test
     *
     * @param RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult RocketgateCCRetrieveTransactionResult
     *
     * @depends it_should_return_a_rocketgate_cc_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_all_data_in_array_format(
        RocketgateCCRetrieveTransactionResult $rocketgateCCRetrieveTransactionResult
    ) {
        $arrayResult = $rocketgateCCRetrieveTransactionResult->toArray();

        $this->assertEquals($this->billerId, $arrayResult['billerId']);
        $this->assertEquals($this->transactionId, $arrayResult['transactionId']);
        $this->assertEquals($this->siteId, $arrayResult['siteId']);
        $this->assertEquals($this->merchantId, $arrayResult['billerFields']['merchantId']);
        $this->assertEquals($_ENV['ROCKETGATE_MERCHANT_PASSWORD_1'], $arrayResult['billerFields']['merchantPassword']);
        $this->assertEquals('RON', $arrayResult['currency']);
        $this->assertEquals('cc', $arrayResult['paymentType']);
        $this->assertEquals('4444', $arrayResult['transactionInformation']['CCTransactionInformation']['last4']);
        $this->assertEquals('123456', $arrayResult['transactionInformation']['CCTransactionInformation']['first6']);
        $this->assertEquals($this->transactionId, $arrayResult['transactionInformation']['transactionId']);
        $this->assertEquals(29.99, $arrayResult['transactionInformation']['amount']);
        $this->assertEquals(12, $arrayResult['transactionInformation']['rebillAmount']);
        $this->assertEquals(1, $arrayResult['transactionInformation']['rebillStart']);
    }
}
