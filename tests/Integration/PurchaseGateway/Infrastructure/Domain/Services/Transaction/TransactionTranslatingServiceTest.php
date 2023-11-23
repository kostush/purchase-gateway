<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Duration;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidAmountException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCreditCardExpirationDate;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\QyssoBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxBreakdown;
use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\AddEpochBillerInteractionInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\CompleteThreeDInterfaceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddEpochBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AbortTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\AddQyssoBillerInteractionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\CompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ExistingCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\GetTransactionDataByAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\LookupThreeDThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewCardPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\NewChequePerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\PerformQyssoRebillTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\EpochBillerInteraction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\ThirdPartyTransaction;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\SimplifiedCompleteThreeDTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ThirdPartyPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Api\EpochApi;
use ProbillerNG\TransactionServiceClient\Api\NetbillingApi;
use ProbillerNG\TransactionServiceClient\Api\QyssoApi;
use ProbillerNG\TransactionServiceClient\Api\RocketgateApi;
use ProbillerNG\TransactionServiceClient\Api\TransactionApi;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProbillerNG\TransactionServiceClient\Configuration;
use ProbillerNG\TransactionServiceClient\Model\InlineResponse200;
use Tests\IntegrationTestCase;
use Throwable;

class TransactionTranslatingServiceTest extends IntegrationTestCase
{
    private const VALID_CARD_HASH = 'm77xlHZiPKVsF9p1/VdzTb+CUwaGBDpuSRxtcb7+j24=';

    private const VALID_PAYMENT_TEMPLATE_ID = 'c7c76f92-1d21-4fa9-b4cc-631335cbcfdb';

    private const VALID_MERCHANT_CUSTOMER_ID_FOR_CARD_HASH = '4165c1cddd82cce24.92280817';

    private const INITIAL_AMOUNT_VALUE = 15.55;

    private const REBILL_AMOUNT_VALUE = 14.44;

    private const INITIAL_DURATION_VALUE = 30;

    private const REBILL_DURATION_VALUE = 365;

    private const INITIAL_TAX_VALUES = [
        'beforeTaxes' => 1.11,
        'taxes' => 2.11,
        'afterTaxes' => 15.55
    ];

    private const REBILL_TAX_VALUES = [
        'beforeTaxes' => 11.11,
        'taxes' => 22.11,
        'afterTaxes' => 14.44
    ];

    /**
     * @var Amount
     */
    private $initialAmount;

    /**
     * @var Duration
     */
    private $validFor;

    /**
     * @var TaxBreakdown
     */
    private $initialTaxBreakdown;

    /**
     * @var Amount
     */
    private $rebillAmount;

    /**
     * @var Duration
     */
    private $repeatEvery;

    /**
     * @var TaxBreakdown
     */
    private $rebillTaxBreakdown;

    /**
     * @var TransactionTranslatingService
     */
    private $transactionTranslatingService;

    /**
     * @var UserInfo
     */
    private $userInfo;

    /**
     * @var BundleRebillChargeInformation
     */
    private $bundleRebillChargeInformation;

    /**
     * @var BundleSingleChargeInformation
     */
    private $bundleSingleChargeInformation;

    /**
     * @var NewCCPaymentInfo
     */
    private $paymentInformation;

    /**
     * @var BillerMapping
     */
    private $billerMapping;

    /**
     * @var BinRouting
     */
    private $binRouting;

    /**
     * @return void
     * @throws InvalidAmountException
     * @throws InvalidCreditCardExpirationDate
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws Throwable
     * @throws Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->initialAmount = Amount::create(self::INITIAL_AMOUNT_VALUE);
        $this->validFor = Duration::create(self::INITIAL_DURATION_VALUE);
        $this->initialTaxBreakdown = TaxBreakdown::create(
            Amount::create(self::INITIAL_TAX_VALUES['beforeTaxes']),
            Amount::create(self::INITIAL_TAX_VALUES['taxes']),
            Amount::create(self::INITIAL_TAX_VALUES['afterTaxes'])
        );

        $this->rebillAmount = Amount::create(self::REBILL_AMOUNT_VALUE);
        $this->repeatEvery = Duration::create(self::REBILL_DURATION_VALUE);
        $this->rebillTaxBreakdown = TaxBreakdown::create(
            Amount::create(self::REBILL_TAX_VALUES['beforeTaxes']),
            Amount::create(self::REBILL_TAX_VALUES['taxes']),
            Amount::create(self::REBILL_TAX_VALUES['afterTaxes'])
        );

        $config = new Configuration();
        $config->setHost(env('TRANSACTION_HOST'));

        $transactionServiceClient = new TransactionServiceClient(
            new TransactionApi(
                null,
                $config
            ),
            new NetbillingApi(
                null,
                $config
            ),
            new EpochApi(
                null,
                $config
            ),
            new QyssoApi(
                null,
                $config
            ),
            new RocketgateApi(
                null,
                $config
            )
        );

        $this->transactionTranslatingService = new TransactionTranslatingService(
            new ExistingCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new NewCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new GetTransactionDataByAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new CompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new SimplifiedCompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddEpochBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddQyssoBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new PerformQyssoRebillTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new ThirdPartyPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AbortTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new LookupThreeDThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new NewChequePerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            )
        );

        $this->userInfo = UserInfo::create(
            CountryCode::create($this->faker->countryCode),
            Ip::create($this->faker->ipv4)
        );

        $this->bundleRebillChargeInformation = BundleRebillChargeInformation::create(
            $this->initialAmount,
            $this->validFor,
            $this->initialTaxBreakdown,
            $this->rebillAmount,
            $this->repeatEvery,
            $this->rebillTaxBreakdown
        );

        $this->bundleSingleChargeInformation = BundleSingleChargeInformation::create(
            $this->initialAmount,
            $this->validFor,
            $this->initialTaxBreakdown
        );

        $this->paymentInformation = NewCCPaymentInfo::create(
            $this->faker->creditCardNumber('Visa'),
            '999',
            '09',
            '2099',
            null
        );

        $rocketgateBillerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_2'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
            '2037',
            'sharedSecret',
            false,
            '4165c1cddd82cce24.92280817',
            $this->faker->uuid
        );

        $this->billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::CAD,
            RocketgateBiller::BILLER_NAME,
            $rocketgateBillerFields
        );

        $this->binRouting = BinRouting::create(
            1,
            '1',
            'Bank name'
        );
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied(): Transaction
    {
        // should remove the following line when we have a merchant account for 3ds
        $this->binRouting = null;

        $result = $this->transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            $this->binRouting,
            true,
            'www.return.url',
            true
        );

        $this->assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     */
    public function it_should_return_a_pending_transaction_for_bundle_rebill_charge_information(
        Transaction $transaction
    ): void
    {
        $this->assertSame($transaction->state(), Transaction::STATUS_PENDING);
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     */
    public function it_should_return_a_transaction_with_a_non_empty_acs(Transaction $transaction): void
    {
        $this->assertNotEmpty($transaction->acs());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     */
    public function it_should_return_a_transaction_with_a_non_empty_pareq(Transaction $transaction): void
    {
        $this->assertNotEmpty($transaction->pareq());
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_transaction_using_a_new_card_when_correct_arguments_are_supplied(): Transaction
    {
        $result = $this->transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            null
        );

        $this->assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     */
    public function it_should_return_an_approved_transaction_for_bundle_rebill_charge_information(
        Transaction $transaction
    ): void
    {
        $this->assertSame($transaction->state(), Transaction::STATUS_APPROVED);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_a_transaction_with_the_aborted_status_when_the_request_did_not_execute(): void
    {
        $transactionServiceClient = new TransactionServiceClient(
            new TransactionApi(),
            new NetbillingApi(),
            new EpochApi(),
            new QyssoApi(),
            new RocketgateApi()
        );
        $transactionTranslatingService = new TransactionTranslatingService(
            new ExistingCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new NewCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new GetTransactionDataByAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new CompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new SimplifiedCompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddEpochBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddQyssoBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new PerformQyssoRebillTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new ThirdPartyPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            $this->createMock(AbortTransactionAdapter::class),
            $this->createMock(LookupThreeDThreeDTransactionAdapter::class),
            $this->createMock(NewChequePerformTransactionAdapter::class)
        );

        $result = $transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            $this->binRouting
        );

        $this->assertSame(Transaction::STATUS_ABORTED, $result->state());
        $this->assertNull($result->transactionId());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_aborted_transaction_when_any_exception_is_thrown_by_the_transaction_adapter(): void
    {
        $clientMock = $this->getMockBuilder(TransactionServiceClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['performRocketgateTransactionWithNewCard'])
            ->getMock();
        $clientMock->method("performRocketgateTransactionWithNewCard")->willThrowException(new \Exception());

        $newCCAdaptor = new NewCardPerformTransactionAdapter($clientMock, new TransactionTranslator());

        $translatorService = new TransactionTranslatingService(
            $this->createMock(ExistingCardPerformTransactionAdapter::class),
            $newCCAdaptor,
            $this->createMock(GetTransactionDataByAdapter::class),
            $this->createMock(CompleteThreeDInterfaceAdapter::class),
            $this->createMock(SimplifiedCompleteThreeDTransactionAdapter::class),
            $this->createMock(AddEpochBillerInteractionInterfaceAdapter::class),
            $this->createMock(AddQyssoBillerInteractionAdapter::class),
            $this->createMock(PerformQyssoRebillTransactionAdapter::class),
            $this->createMock(ThirdPartyPerformTransactionAdapter::class),
            $this->createMock(AbortTransactionAdapter::class),
            $this->createMock(LookupThreeDThreeDTransactionAdapter::class),
            $this->createMock(NewChequePerformTransactionAdapter::class)
        );

        $result = $translatorService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            $this->binRouting
        );

        $this->assertSame(Transaction::STATUS_ABORTED, $result->state());
        $this->assertNull($result->transactionId());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_approved_transaction_for_bundle_single_charge_charge_information_when_using_a_new_card(): void
    {
        $transaction = $this->transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleSingleChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            null
        );

        $this->assertSame($transaction->state(), Transaction::STATUS_APPROVED);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_approved_transaction_for_when_bin_routing_is_missing_using_a_new_card(): void
    {
        $transaction = $this->transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleSingleChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            null
        );

        $this->assertSame($transaction->state(), Transaction::STATUS_APPROVED);
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     * @throws Throwable
     */
    public function it_should_return_a_transaction_using_an_existing_card_when_correct_arguments_are_supplied(): Transaction
    {
        $this->paymentInformation = ExistingCCPaymentInfo::create(
            self::VALID_CARD_HASH,
            self::VALID_PAYMENT_TEMPLATE_ID,
            null,
            []
        );
        $this->billerMapping->billerFields()->merchantCustomerId(self::VALID_MERCHANT_CUSTOMER_ID_FOR_CARD_HASH);

        $result = $this->transactionTranslatingService->performTransactionWithExistingCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            $this->binRouting
        );

        $this->assertInstanceOf(Transaction::class, $result);

        return $result;
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws Throwable
     */
    public function it_should_return_an_approved_transaction_for_when_bin_routing_is_missing_using_an_existing_card(): void
    {
        $this->paymentInformation = ExistingCCPaymentInfo::create(
            self::VALID_CARD_HASH,
            self::VALID_PAYMENT_TEMPLATE_ID,
            null,
            []
        );
        $this->billerMapping->billerFields()->merchantCustomerId(self::VALID_MERCHANT_CUSTOMER_ID_FOR_CARD_HASH);

        $transaction = $this->transactionTranslatingService->performTransactionWithExistingCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleSingleChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            null
        );
        $this->assertSame(Transaction::STATUS_APPROVED, $transaction->state());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_approved_transaction_when_complete_threeD(
        Transaction $transaction
    ): void {
        $pares = str_replace(
            'PAREQ',
            'PARES',
            $transaction->pareq()
        );

        $transaction = $this->transactionTranslatingService->performCompleteThreeDTransaction(
            $transaction->transactionId(),
            $pares,
            null,
            SessionId::create()
        );
        $this->assertSame(Transaction::STATUS_APPROVED, $transaction->state());
    }

    /**
     * @test
     * @depends it_should_return_a_transaction_using_a_new_card_with_three_d_when_correct_arguments_are_supplied
     *
     * @param Transaction $transaction The transaction
     *
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_aborted_transaction_when_complete_threeD_with_invalid_pares(
        Transaction $transaction
    ): void
    {
        $pares = str_replace(
            'PAREQ',
            'INVALID',
            $transaction->pareq()
        );

        $transaction = $this->transactionTranslatingService->performCompleteThreeDTransaction(
            $transaction->transactionId(),
            $pares,
            null,
            SessionId::create()
        );
        $this->assertSame(Transaction::STATUS_ABORTED, $transaction->state());
    }

    /**
     * @test
     * @return EpochBillerInteraction
     * @throws \Exception
     */
    public function it_should_return_epoch_biller_interaction_result(): EpochBillerInteraction
    {
        $transactionServiceClient = $this->createMock(TransactionServiceClient::class);

        $transactionTranslatingService = new TransactionTranslatingService(
            new ExistingCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new NewCardPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new GetTransactionDataByAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new CompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new SimplifiedCompleteThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddEpochBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AddQyssoBillerInteractionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new PerformQyssoRebillTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new ThirdPartyPerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new AbortTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new LookupThreeDThreeDTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            ),
            new NewChequePerformTransactionAdapter(
                $transactionServiceClient,
                new TransactionTranslator()
            )
        );

        $transactionServiceResponse = new InlineResponse200(
            [
                'status' => 'approved',
                'paymentType' => 'cc',
                'paymentMethod' => 'visa'
            ]
        );

        $transactionServiceClient->method('addEpochBillerInteraction')
            ->willReturn($transactionServiceResponse);

        $result = $transactionTranslatingService->addEpochBillerInteraction(
            TransactionId::create(),
            SessionId::create(),
            []
        );

        $this->assertInstanceOf(EpochBillerInteraction::class, $result);

        return $result;
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_pending_transaction_with_third_party(): ThirdPartyTransaction
    {
        $this->markTestSkipped('As we are having issue with Qysso URL, untill they fix it we will mark this test skipped');
        $chargeInformation = $this->createMock(BundleSingleChargeInformation::class);
        $chargeInformation->method('initialAmount')->willReturn(Amount::create(12.3));

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            EpochBiller::BILLER_NAME,
            EpochBillerFields::create($_ENV['EPOCH_CLIENT_ID'], $_ENV['EPOCH_CLIENT_KEY'], $_ENV['EPOCH_CLIENT_VERIFICATION_KEY'])
        );

        $transaction = $this->transactionTranslatingService->performTransactionWithThirdParty(
            $this->createSite(),
            [],
            new EpochBiller(),
            CurrencyCode::create('USD'),
            $this->createUserInfo(),
            $chargeInformation,
            CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null),
            $billerMapping,
            SessionId::create(),
            $this->faker->url,
            null,
            null,
            null,
            null
        );
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->state());

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_a_pending_transaction_with_third_party
     *
     * @param ThirdPartyTransaction $transaction The transaction
     *
     * @return void
     */
    public function it_should_return_a_pending_transaction_with_redirect_url(
        ThirdPartyTransaction $transaction
    ): void
    {
        $this->assertNotEmpty($transaction->redirectUrl());
    }

    /**
     * @test
     * @return ThirdPartyTransaction
     * @throws Exception
     * @throws InvalidAmountException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidCurrency
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     */
    public function it_should_return_a_aborted_transaction_with_third_party(): ThirdPartyTransaction
    {
        $this->markTestSkipped('We no longer support Epoch biller.');

        $chargeInformation = $this->createMock(BundleSingleChargeInformation::class);
        $chargeInformation->method('initialAmount')->willReturn(Amount::create(12.3));

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            EpochBiller::BILLER_NAME,
            EpochBillerFields::create('clientId', 'clientKey', 'clientVerificationKey')
        );

        $transaction = $this->transactionTranslatingService->performTransactionWithThirdParty(
            $this->createSite(),
            [],
            new EpochBiller(),
            CurrencyCode::create('USD'),
            $this->createUserInfo(),
            $chargeInformation,
            CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null),
            $billerMapping,
            SessionId::create(),
            $this->faker->url,
            null,
            null,
            null,
            null
        );
        $this->assertSame(Transaction::STATUS_ABORTED, $transaction->state());

        return $transaction;
    }

    /**
     * @test
     * @throws Throwable
     * @return Transaction
     */
    public function it_should_return_a_pending_transaction_with_3ds_2x_on_sale(): Transaction
    {
        $rocketgateBillerFields = RocketgateBillerFields::create(
            '1498147306',
            'Pyb47mQ2UQXYqfds',
            '8000',
            'sharedSecret',
            false,
            '4165c1cddd82cce24.92280817',
            $this->faker->uuid
        );

        $this->billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::CAD,
            RocketgateBiller::BILLER_NAME,
            $rocketgateBillerFields
        );

        $transaction = $this->transactionTranslatingService->performTransactionWithNewCard(
            SiteId::create(),
            new RocketgateBiller(),
            CurrencyCode::USD(),
            $this->userInfo,
            $this->bundleRebillChargeInformation,
            $this->paymentInformation,
            $this->billerMapping,
            SessionId::create(),
            null,
            true
        );

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->state());
        $this->assertNotEmpty($transaction->deviceCollectionJwt());
        $this->assertNotEmpty($transaction->deviceCollectionUrl());

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_a_pending_transaction_with_3ds_2x_on_sale
     *
     * @param Transaction $transaction Transaction.
     *
     * @return void
     * @throws InvalidCreditCardExpirationDate
     * @throws Throwable
     * @throws Exception
     */
    public function it_should_return_a_pending_transaction_with_3ds_2x_when_lookup(Transaction $transaction): void
    {
        $transaction = $this->transactionTranslatingService->performLookupTransaction(
            $transaction->transactionId(),
            NewCCPaymentInfo::create(
                $this->paymentInformation->ccNumber(),
                $this->paymentInformation->cvv(),
                $this->paymentInformation->expirationMonth(),
                $this->paymentInformation->expirationYear(),
                'visa'
            ),
            $this->faker->url,
            '2',
            'rocketgate',
            SessionId::create()
        );

        $this->assertSame(Transaction::STATUS_PENDING, $transaction->state());
        $this->assertNotEmpty($transaction->threeDStepUpJwt());
        $this->assertNotEmpty($transaction->threeDStepUpUrl());
        $this->assertNotEmpty($transaction->md());
        $this->assertSame(2, $transaction->threeDVersion());
    }

    /**
     * @test
     * @return Transaction
     * @throws \Exception
     */
    public function it_should_return_a_pending_transaction_with_qysso_as_third_party(): ThirdPartyTransaction
    {
        $this->markTestSkipped('As we are having issue with Qysso URL, untill they fix it we will mark this test skipped');
        $chargeInformation = $this->createMock(BundleSingleChargeInformation::class);
        $chargeInformation->method('initialAmount')->willReturn(Amount::create(12.3));

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            QyssoBiller::BILLER_NAME,
            QyssoBillerFields::create($_ENV['QYSSO_COMPANY_NUM'], $_ENV['QYSSO_PERSONAL_HASH_KEY'])
        );

        $transaction = $this->transactionTranslatingService->performTransactionWithThirdParty(
            $this->createSite(),
            [],
            new QyssoBiller(),
            CurrencyCode::create('USD'),
            $this->createUserInfo(),
            $chargeInformation,
            OtherPaymentTypeInfo::build('banktransfer', null),
            $billerMapping,
            SessionId::create(),
            $this->faker->url,
            null,
            null,
            'zelle',
            null
        );
        $this->assertSame(Transaction::STATUS_PENDING, $transaction->state());

        return $transaction;
    }

    /**
     * @test
     * @depends it_should_return_a_pending_transaction_with_qysso_as_third_party
     * @param ThirdPartyTransaction $transaction The transaction
     * @return void
     */
    public function it_should_return_a_pending_transaction_for_qysso_with_redirect_url(
        ThirdPartyTransaction $transaction
    ): void {
        $this->assertNotEmpty($transaction->redirectUrl());
    }
}
