<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\LookupThreeDThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProbillerNG\TransactionServiceClient\Model\CreditCardInformationWithoutMember;
use ProbillerNG\TransactionServiceClient\Model\CreditCardLookup;
use ProbillerNG\TransactionServiceClient\Model\LookupRequestBody;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use Tests\UnitTestCase;

class PerformLookupTransactionAdapterTest extends UnitTestCase
{
    /**
     * @var TransactionServiceClient
     */
    private $transactionServiceClient;

    /**
     * @var TransactionTranslator
     */
    private $transactionTranslator;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var NewCCPaymentInfo|\ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo
     */
    private $paymentInfo;

    /**
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transactionServiceClient = $this->createMock(TransactionServiceClient::class);
        $this->transactionTranslator    = $this->createMock(TransactionTranslator::class);
        $this->sessionId                = SessionId::create();
        $this->paymentInfo              = NewCCPaymentInfo::create(
            $this->faker->creditCardNumber,
            '123',
            '12',
            '2030',
            'visa'
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_a_retrieve_transaction_result_when_valid_data_provided()
    {
        $processTransactionWithBillerAdapter = new LookupThreeDThreeDTransactionAdapter(
            $this->createMock(TransactionServiceClient::class),
            $this->createMock(TransactionTranslator::class)
        );

        $this->assertInstanceOf(LookupThreeDThreeDTransactionAdapter::class, $processTransactionWithBillerAdapter);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws \Throwable
     */
    public function is_should_call_perform_lookup_transaction_on_transaction_service_client(): void
    {
        $redirectUrl       = $this->faker->url;
        $transactionId = TransactionId::createFromString($this->faker->uuid);
        $lookupRequestBody = new LookupRequestBody();
        $lookupRequestBody->setPreviousTransactionId((string) $transactionId);
        $lookupRequestBody->setDeviceFingerprintingId('2');
        $lookupRequestBody->setRedirectUrl($redirectUrl);

        $requestPayment = new CreditCardLookup();
        $requestPayment->setMethod('cc');
        $cardInfo = new CreditCardInformationWithoutMember();

        $cardInfo->setCvv('123')
            ->setNumber($this->paymentInfo->ccNumber())
            ->setExpirationMonth('12')
            ->setExpirationYear('2030');
        $requestPayment->setInformation($cardInfo);

        $lookupRequestBody->setPayment($requestPayment);
        $this->transactionServiceClient->expects($this->once())->method('lookupThreedsTransaction')->with(
            $lookupRequestBody,
            RocketgateBiller::BILLER_NAME,
            (string) $this->sessionId
        );

        $lookupAdapter = new LookupThreeDThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $lookupAdapter->lookupTransaction(
            $transactionId,
            $this->paymentInfo,
            $redirectUrl,
            '2',
            RocketgateBiller::BILLER_NAME,
            $this->sessionId
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function is_should_call_translate_on_transaction_translator(): void
    {
        $clientTransaction = $this->createMock(ClientTransaction::class);

        $this->transactionServiceClient->method('lookupThreedsTransaction')->willReturn($clientTransaction);

        $this->transactionTranslator->expects($this->once())->method('translate')->with(
            $clientTransaction,
            true,
            RocketgateBiller::BILLER_NAME
        );

        $lookupAdapter = new LookupThreeDThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $lookupAdapter->lookupTransaction(
            TransactionId::createFromString($this->faker->uuid),
            $this->paymentInfo,
            $this->faker->url,
            '2',
            RocketgateBiller::BILLER_NAME,
            $this->sessionId
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function is_should_return_an_aborted_transaction_when_an_exception_is_thrown(): void
    {
        $this->transactionServiceClient->method('lookupThreedsTransaction')->willThrowException(
            new \Exception()
        );

        $lookupAdapter = new LookupThreeDThreeDTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $transaction = $lookupAdapter->lookupTransaction(
            TransactionId::createFromString($this->faker->uuid),
            $this->paymentInfo,
            $this->faker->url,
            '2',
            RocketgateBiller::BILLER_NAME,
            $this->sessionId
        );

        $this->assertSame(Transaction::STATUS_ABORTED, $transaction->state());
    }
}
