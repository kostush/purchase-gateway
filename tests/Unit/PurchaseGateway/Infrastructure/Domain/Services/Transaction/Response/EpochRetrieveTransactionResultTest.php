<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\BillerTransactionCollection;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochRetrieveTransactionResult;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\MemberInformation;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransaction;
use ProbillerNG\TransactionServiceClient\Model\RetrieveTransactionBillerTransactions;
use Tests\UnitTestCase;

class EpochRetrieveTransactionResultTest extends UnitTestCase
{

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
    private $currency;

    /**
     * Init
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->siteId        = '3f26bc1b-6dcc-4de8-96f1-30e4218eedb8';
        $this->billerId      = '966a0200-58c9-4f23-80be-de534948ad52';
        $this->currency      = 'USD';
    }

    /**
     * @test
     * @return EpochRetrieveTransactionResult
     */
    public function it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent(): EpochRetrieveTransactionResult
    {
        $retrieveTransaction = $this->createMock(RetrieveTransaction::class);
        $retrieveTransaction->method('getBillerId')->willReturn($this->billerId);
        $retrieveTransaction->method('getCurrency')->willReturn($this->currency);
        $retrieveTransaction->method('getSiteId')->willReturn($this->siteId);
        $retrieveTransaction->method('getPaymentMethod')->willReturn('visa');
        $retrieveTransaction->method('getBillerTransactions')->willReturn(
            [
                new RetrieveTransactionBillerTransactions(
                    [
                        'billerTransactionId' => '1206684681',
                        'piCode'              => 'InvoiceProduct68252',
                        'billerMemberId'      => '2354302288',
                        'ans'                 => 'Y245724UU |2354302288'
                    ]
                )
            ]
        );

        $memberInformation        = $this->createMock(MemberInformation::class);
        $ccTransactionInformation = $this->createMock(NewCCTransactionInformation::class);

        $billerFields = EpochBillerFields::create(
            '1234',
            'clientKey',
            'clientVerificationKey'
        );

        $epochRetrieveTransactionResult = new EpochRetrieveTransactionResult(
            $retrieveTransaction,
            $memberInformation,
            $ccTransactionInformation,
            $billerFields
        );

        $this->assertInstanceOf(EpochRetrieveTransactionResult::class, $epochRetrieveTransactionResult);

        return $epochRetrieveTransactionResult;
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_abiller_transaction_id(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $this->assertEquals('1206684681', $epochRetrieveTransactionResult->billerTransactionId());
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_correct_biller_transactions(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $billerTransactionCollection = new BillerTransactionCollection();
        $billerTransactionCollection->add(
            EpochBillerTransaction::create(
                'InvoiceProduct68252',
                '2354302288',
                '1206684681',
                'Y245724UU |2354302288'
            )
        );

        $this->assertEquals($billerTransactionCollection, $epochRetrieveTransactionResult->billerTransactions());
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_payment_sub_type(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $this->assertEquals('visa', $epochRetrieveTransactionResult->paymentSubtype());
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_return_false_for_secured_with_3d(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $this->assertFalse($epochRetrieveTransactionResult->securedWithThreeD());
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_correct_biller_fields(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $billerFields = EpochBillerFields::create(
            '1234',
            'clientKey',
            'clientVerificationKey'
        );

        $this->assertEquals($billerFields, $epochRetrieveTransactionResult->billerFields());
    }

    /**
     * @test
     * @param EpochRetrieveTransactionResult $epochRetrieveTransactionResult Epoch retrieve transaction result
     * @depends it_should_return_a_epoch_retrieve_transaction_result_object_if_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_all_data_in_array_format(
        EpochRetrieveTransactionResult $epochRetrieveTransactionResult
    ): void {
        $arrayResult = $epochRetrieveTransactionResult->toArray();

        $this->assertEquals($this->billerId, $arrayResult['billerId']);
        $this->assertEquals($this->currency, $arrayResult['currency']);
        $this->assertEquals($this->siteId, $arrayResult['siteId']);
        $this->assertEquals('1234', $arrayResult['billerFields']['clientId']);
        $this->assertEquals('clientKey', $arrayResult['billerFields']['clientKey']);
        $this->assertEquals('clientVerificationKey', $arrayResult['billerFields']['clientVerificationKey']);
    }
}
