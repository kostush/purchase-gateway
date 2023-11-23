<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use ProBillerNG\PurchaseGateway\Domain\Model\Amount;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleSingleChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\EpochBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxType;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\ThirdPartyPerformTransactionAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\TransactionTranslator;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBody;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyBillerFields;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPayment;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPaymentInformation;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyPaymentInformationMember;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyTax;
use ProbillerNG\TransactionServiceClient\Model\EpochTransactionRequestBodyTaxInitialAmount;
use ProbillerNG\TransactionServiceClient\Model\Transaction as ClientTransaction;
use Tests\UnitTestCase;

class ThirdPartyTransactionAdapterTest extends UnitTestCase
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
     * @var UserInfo
     */
    private $userInfo;

    /**
     * @var BillerMapping
     */
    private $billerMapping;

    /**
     * @var BundleSingleChargeInformation
     */
    private $chargeInformation;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->transactionServiceClient = $this->createMock(TransactionServiceClient::class);
        $this->transactionTranslator    = $this->createMock(TransactionTranslator::class);

        $this->userInfo = $this->createUserInfo();

        $this->billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            EpochBiller::BILLER_NAME,
            EpochBillerFields::create('clientId', 'clientKey', 'clientVerificationKey')
        );

        $this->sessionId         = SessionId::create();
        $this->chargeInformation = $this->createMock(BundleSingleChargeInformation::class);
        $this->chargeInformation->method('initialAmount')->willReturn(Amount::create(12.3));
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_call_perform_transaction_on_transaction_service_client(): void
    {
        $taxInfo = new EpochTransactionRequestBodyTax();

        $taxBreakDown = [
            'initialAmount' =>  [
                "beforeTaxes" => 9.99,
                "taxes" => 0,
                "afterTaxes" => 9.99
            ]
        ];

        $this->chargeInformation->method('fullTaxBreakDownArray')->willReturn($taxBreakDown);

        $initAmount = new EpochTransactionRequestBodyTaxInitialAmount($taxBreakDown['initialAmount']);
        $taxInfo->setInitialAmount($initAmount);

        $epochRequestBody = new EpochTransactionRequestBody();

        $site            = $this->createSite();
        $biller          = new EpochBiller();
        $paymentInfo     = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);
        $redirectUrl     = $this->faker->url;
        $notificationUrl = env('POSTBACK_SERVICE_HOST') . '/api/v1/postback/' . EpochBillerFields::POSTBACK_ID;

        $billerFields = new EpochTransactionRequestBodyBillerFields($this->billerMapping->billerFields()->toArray());
        $billerFields->setRedirectUrl($redirectUrl);
        $billerFields->setNotificationUrl($notificationUrl);

        $epochRequestBody->setSiteId((string) $site->id());
        $epochRequestBody->setSiteName((string) $site->url());
        $epochRequestBody->setBillerId($biller->id())
            ->setAmount(12.3)
            ->setCurrency('USD')
            ->setBillerFields($billerFields);

        $epochRequestBody->setTax($taxInfo);

        $payment = new EpochTransactionRequestBodyPayment();

        $payment->setType(CCPaymentInfo::PAYMENT_TYPE);

        $paymentData = new EpochTransactionRequestBodyPaymentInformation();

        $member = new EpochTransactionRequestBodyPaymentInformationMember();

        $member->setUsername((string) $this->userInfo->username())
            ->setPassword((string) $this->userInfo->password())
            ->setFirstName((string) $this->userInfo->firstName())
            ->setLastName((string) $this->userInfo->lastName())
            ->setEmail((string) $this->userInfo->email())
            ->setCountry((string) $this->userInfo->countryCode())
            ->setZipCode((string) $this->userInfo->zipCode())
            ->setState((string) $this->userInfo->state())
            ->setCity((string) $this->userInfo->city())
            ->setAddress((string) $this->userInfo->address())
            ->setPhone((string) $this->userInfo->phoneNumber());

        $paymentData->setMember($member);
        $payment->setInformation($paymentData);

        $epochRequestBody->setPayment($payment);

        $this->transactionServiceClient->expects($this->once())->method('performEpochTransaction')->with(
            $epochRequestBody,
            (string) $this->sessionId
        );

        $epochAdapter = new ThirdPartyPerformTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $taxType = TaxType::create(null);

        $epochAdapter->performTransaction(
            $site,
            [],
            $biller,
            CurrencyCode::create('USD'),
            $this->userInfo,
            $this->chargeInformation,
            $paymentInfo,
            $this->billerMapping,
            $this->sessionId,
            $redirectUrl,
            TaxInformation::create(null,null,null, null, $taxType),
            null,
            null,
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_call_translate_on_transaction_translator(): void
    {
        $clientTransaction = $this->createMock(ClientTransaction::class);

        $this->transactionServiceClient->method('performEpochTransaction')->willReturn($clientTransaction);

        $this->transactionTranslator->expects($this->once())->method('translateThirdPartyResponse')->with(
            $clientTransaction,
            EpochBiller::BILLER_NAME
        );

        $epochAdapter = new ThirdPartyPerformTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $epochAdapter->performTransaction(
            $this->createSite(),
            [],
            new EpochBiller(),
            CurrencyCode::create('USD'),
            $this->userInfo,
            $this->chargeInformation,
            CCPaymentInfo::build('cc', null),
            $this->billerMapping,
            $this->sessionId,
            $this->faker->url,
            null,
            null,
            null,
            null
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_aborted_transaction_when_an_exception_is_thrown(): void
    {
        $this->transactionServiceClient->method('performEpochTransaction')->willThrowException(
            new \Exception()
        );

        $epochAdapter = new ThirdPartyPerformTransactionAdapter(
            $this->transactionServiceClient,
            $this->transactionTranslator
        );

        $transaction = $epochAdapter->performTransaction(
            $this->createSite(),
            [],
            new EpochBiller(),
            CurrencyCode::create('USD'),
            $this->userInfo,
            $this->chargeInformation,
            CCPaymentInfo::build('cc', null),
            $this->billerMapping,
            SessionId::create(),
            $this->faker->url,
            null,
            null,
            null,
            null
        );

        $this->assertSame(Transaction::STATUS_ABORTED, $transaction->state());
    }
}
