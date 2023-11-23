<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseProcess;

use Odesk\Phystrix\Exception\ApcNotLoadedException;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\FraudFailedPaymentTemplateValidation;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ExistingPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\DuplicatedPurchaseProcessRequestException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException;
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
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentTemplateLastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\NetbillingBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\CreatePaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\LegacyImportService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\RetrieveFraudRecommendationForExistingCardOnProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\CircuitBreakerValidatePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\Exceptions\RetrievePaymentTemplateException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\RetrievePaymentTemplatesServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateCommand;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\ValidatePaymentTemplateServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PaymentTemplate\PaymentTemplateTranslatingService as PaymentTemplateTranslatingServiceImplementation;
use ReflectionClass;
use ReflectionException;
use Tests\IntegrationTestCase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use Throwable;

class ExistingPaymentProcessCommandHandlerTest extends IntegrationTestCase
{
    private const TEMPLATE_ID      = '9329f08d-f7f2-4640-8b70-6236c4519aa8';
    private const FIRST_SIX        = '123456';
    private const LAST_FOUR        = '9876';
    private const EXPIRATION_YEAR  = '2019';
    private const EXPIRATION_MONTH = '11';
    private const LAST_USED_DATE   = '2019-08-11 15:15:25';
    private const CREATED_AT       = '2019-08-11 15:15:25';

    private const SUBSCRIPTION_ID = '6a71e3b9-65a0-34da-8ebd-031f916e971e';
    private const MEMBER_ID       = '70901d6d-1621-4466-bd46-2f03ac455ad5';
    private const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';
    private const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

    /**
     * @var string
     */
    protected $templateId;

    /**
     * @var string
     */
    protected $firstSix;

    /**
     * @var string
     */
    protected $lastFour;

    /**
     * @var string
     */
    protected $expirationYear;

    /**
     * @var string
     */
    protected $expirationMonth;

    /**
     * @var string
     */
    protected $lastUsedDate;

    /**
     * @var string
     */
    protected $createdAt;

    /**
     * @var array
     */
    protected $rocketgateBillerFieldsForPaymentTemplate;

    /**
     * @var array
     */
    protected $netbillingBillerFieldsForPaymentTemplate;

    /**
     * @var RocketgateBillerFields
     */
    protected $rocketgateBillerFields;

    /**
     * @var NetbillingBillerFields
     */
    protected $netbillingBillerFields;

    /**
     * @var MockObject|CreatePaymentTemplateService
     */
    private $createPaymentTemplateService;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->templateId                               = self::TEMPLATE_ID;
        $this->firstSix                                 = self::FIRST_SIX;
        $this->lastFour                                 = self::LAST_FOUR;
        $this->expirationYear                           = self::EXPIRATION_YEAR;
        $this->expirationMonth                          = self::EXPIRATION_MONTH;
        $this->lastUsedDate                             = self::LAST_USED_DATE;
        $this->createdAt                                = self::CREATED_AT;
        $this->rocketgateBillerFields                   = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_2'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
            '2037',
            'sharedSecret',
            true
        );

        $this->rocketgateBillerFieldsForPaymentTemplate = [
            'cardHash'           => $_ENV['ROCKETGATE_CARD_HASH_1'],
            'merchantCustomerId' => '4165c1cddd82cce24.92280817'
        ];

        $this->netbillingBillerFields = NetbillingBillerFields::create(
            $_ENV['NETBILLING_ACCOUNT_ID'],
            $_ENV['NETBILLING_SITE_TAG'],
            'INT\/PX#100XTxEP',
            $_ENV['NETBILLING_MERCHANT_PASSWORD']
        );

        $this->createPaymentTemplateService = $this->createMock(CreatePaymentTemplateService::class);

        $this->netbillingBillerFieldsForPaymentTemplate = [
            'originId' => '113890225261',
            'cardHash' => $_ENV['NETBILLING_CARD_HASH']
        ];;
    }

    /**
     * @test
     * @return    array
     * @throws Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_return_a_purchase_process_dto_with_rocketgate_payment_template(): array
    {
        $billerMapping = $this->getBillerMappings(RocketgateBiller::BILLER_NAME, $this->rocketgateBillerFields);

        $billerMappingServiceMock = $this->createMock(BillerMappingService::class);
        $billerMappingServiceMock->method('retrieveBillerMapping')->willReturn(
            $billerMapping
        );

        $paymentTemplate = $this->getPaymentTemplate(
            RocketgateBiller::BILLER_NAME,
            $this->rocketgateBillerFieldsForPaymentTemplate
        );

        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $paymentTemplateTranslatingServiceMock = $this->createMock(PaymentTemplateTranslatingService::class);
        $paymentTemplateTranslatingServiceMock->method('retrievePaymentTemplate')->willReturn(
            $paymentTemplate
        );

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => RocketgateBiller::BILLER_NAME,
                'requiresIdentityVerification' => false,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        /** @var ExistingPaymentProcessCommandHandler $handler */
        $handler = $this->getMockForExistingPaymentProcessCommandHandler(
            $billerMappingServiceMock,
            $processHandler,
            $paymentTemplateService
        );

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        $dto = $handler->execute($command);
        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        return $dto->jsonSerialize();
    }

    /**
     * @return MemberProfileGatewayService
     * @throws Exception
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoUsername
     */
    private function getMemberProfileGatewayService():MemberProfileGatewayService
    {
        $memberProfileGatewayService      = $this->createMock(MemberProfileGatewayService::class);
        $memberInfo = $this->createMock(MemberInfo::class);
        $memberInfo->method('username')->willReturn(Username::create($this->faker->userName));
        $memberInfo->method('email')->willReturn(Email::create($this->faker->email));
        $memberProfileGatewayService->method('retrieveMemberProfile')->willReturn($memberInfo);

        return $memberProfileGatewayService;
    }

    /**
     * @param                            $billerMappingServiceMock
     * @param                            $processHandler
     * @param                            $paymentTemplateService
     * @param EventIngestionService|null $eventIngestionService
     *
     * @return MockObject
     * @throws \Exception
     */
    private function getMockForExistingPaymentProcessCommandHandler(
        $billerMappingServiceMock,
        $processHandler,
        $paymentTemplateService,
        EventIngestionService $eventIngestionService = null
    ) {
        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        if (config('app.feature.legacy_api_import')) {
            $repo = $this->createMock(PurchaseRepository::class);

            $legacyImportService        = $this->createMock(LegacyImportService::class);
            $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
            $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

            $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);
        } else {
            $purchaseService = app()->make(PurchaseService::class);
        }

        return $this->getMockBuilder(ExistingPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $billerMappingServiceMock,
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $purchaseService,
                    $httpCommandDTOAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    app()->make(TransactionService::class),
                    $paymentTemplateService,
                    $this->createMock(RetrieveFraudRecommendationForExistingCardOnProcess::class),
                    $this->getMemberProfileGatewayService(),
                    $eventIngestionService??$this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class)
                ]
            )
            ->onlyMethods(
                [
                    'retrieveRoutingCodes',
                    'shipBiProcessedPurchaseEvent'
                ]
            )
            ->getMock();
    }

    /**
     * @param string       $billerName   Biller Name
     * @param BillerFields $billerFields Biller Fields
     * @return BillerMapping
     */
    private function getBillerMappings(string $billerName, BillerFields $billerFields): BillerMapping
    {
        if ($billerName == NetbillingBiller::BILLER_NAME) {
            $billerFields = $this->netbillingBillerFields;
        } else {
            $billerFields = $this->rocketgateBillerFields;
        }

        return BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            RocketgateBiller::BILLER_NAME,
            $billerFields
        );
    }

    /**
     * @param string $billerName                     biller name
     * @param array  $billerFieldsForPaymentTemplate biller fields in payment template
     * @return PaymentTemplate
     */
    private function getPaymentTemplate(string $billerName, array $billerFieldsForPaymentTemplate): PaymentTemplate
    {
        if ($billerName == NetbillingBiller::BILLER_NAME) {
            $billerFieldsForPaymentTemplate = $this->netbillingBillerFieldsForPaymentTemplate;
        } else {
            $billerFieldsForPaymentTemplate = $this->rocketgateBillerFieldsForPaymentTemplate;
        }

        return PaymentTemplate::create(
            $this->templateId,
            $this->firstSix,
            $this->lastFour,
            $this->expirationYear,
            $this->expirationMonth,
            $this->lastUsedDate,
            $this->createdAt,
            $billerName,
            $billerFieldsForPaymentTemplate
        );
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_success_with_true_value(array $response)
    {
        $this->assertTrue($response['success']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_sessionId_key(array $response)
    {
        $this->assertArrayHasKey('sessionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_purchaseId_key(array $response)
    {
        $this->assertArrayHasKey('purchaseId', $response);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_itemId_key(array $response)
    {
        $this->assertArrayHasKey('itemId', $response);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_memberId_key(array $response)
    {
        $this->assertEquals(self::MEMBER_ID, $response['memberId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_bundleId_key(array $response)
    {
        $this->assertEquals(self::BUNDLE_ID, $response['bundleId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_addonId_key(array $response)
    {
        $this->assertEquals(self::ADDON_ID, $response['addonId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_subscriptionId_key(array $response)
    {
        $this->assertEquals(self::SUBSCRIPTION_ID, $response['subscriptionId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_transactionId_key(array $response)
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_billerName_key(array $response)
    {
        $this->assertArrayHasKey('billerName', $response);
        $this->assertEquals(RocketgateBiller::BILLER_NAME, $response['billerName']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto_with_rocketgate_payment_template
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_digest_key(array $response)
    {
        $this->assertArrayHasKey('digest', $response);
    }

    /**
     * @test
     * @return    array
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws InvalidCurrency
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws UnsupportedPaymentMethodException
     * @throws UnknownBillerNameException
     * @throws ReflectionException
     */
    public function it_should_return_a_purchase_process_dto_with_netbilling_payment_template(): array
    {

        $billerMapping = $this->getBillerMappings(NetbillingBiller::BILLER_NAME, $this->netbillingBillerFields);

        $billerMappingServiceMock = $this->createMock(BillerMappingService::class);
        $billerMappingServiceMock->method('retrieveBillerMapping')->willReturn(
            $billerMapping
        );

        $paymentTemplate = $this->getPaymentTemplate(
            NetbillingBiller::BILLER_NAME,
            $this->netbillingBillerFieldsForPaymentTemplate
        );

        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $paymentTemplateTranslatingServiceMock = $this->createMock(PaymentTemplateTranslatingService::class);
        $paymentTemplateTranslatingServiceMock->method('retrievePaymentTemplate')->willReturn(
            $paymentTemplate
        );

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);


        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => 'netbilling',
                'requiresIdentityVerification' => false,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        if (config('app.feature.legacy_api_import')) {
            $repo = $this->createMock(PurchaseRepository::class);

            $legacyImportService        = $this->createMock(LegacyImportService::class);
            $purchaseProcessedEventMock = $this->createMock(PurchaseProcessed::class);
            $legacyImportService->method('handlerLegacyImportByApiEndPoint')->willReturn($purchaseProcessedEventMock);

            $purchaseService = new PurchaseService($repo, $legacyImportService, $this->createPaymentTemplateService);
        } else {
            $purchaseService = app()->make(PurchaseService::class);
        }

        $handler = $this->getMockBuilder(ExistingPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $billerMappingServiceMock,
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $purchaseService,
                    $httpCommandDTOAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    app()->make(TransactionService::class),
                    $paymentTemplateService,
                    $this->createMock(RetrieveFraudRecommendationForExistingCardOnProcess::class),
                    $this->getMemberProfileGatewayService(),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class)
                ]
            )
            ->onlyMethods(
                [
                    'retrieveRoutingCodes',
                    'shipBiProcessedPurchaseEvent'
                ]
            )
            ->getMock();

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        $dto = $handler->execute($command);
        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        return $dto->jsonSerialize();
    }

    /**
     * @test
     * @return ExistingPaymentProcessCommandHandler
     * @throws ApcNotLoadedException
     * @throws Exception
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
     * @throws InvalidCurrency
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_invalid_payment_template_last_four_when_invalid_last_four_provided()
    {
        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new InvalidPaymentTemplateLastFour());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $paymentTemplateAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $paymentTemplateTranslatingServiceMock = $this->getMockBuilder(PaymentTemplateTranslatingServiceImplementation::class)
            ->setConstructorArgs(
                [
                    $this->createMock(RetrievePaymentTemplatesServiceAdapter::class),
                    $this->createMock(RetrievePaymentTemplateServiceAdapter::class),
                    $paymentTemplateAdapter
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => RocketgateBiller::BILLER_NAME,
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        /** @var ExistingPaymentProcessCommandHandler $handler */
        $handler = $this->getMockForExistingPaymentProcessCommandHandler(
            $this->createMock(BillerMappingService::class),
            $processHandler,
            $paymentTemplateService
        );

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        try {
            $handler->execute($command);
        } catch (\Exception $exception) {
            $this->assertInstanceOf(InvalidPaymentTemplateLastFour::class, $exception);
        }

        return $handler;
    }

    /**
     * @test
     * @depends it_should_throw_invalid_payment_template_last_four_when_invalid_last_four_provided
     * @param ExistingPaymentProcessCommandHandler $handler Handler
     * @return void
     * @throws ReflectionException
     */
    public function it_should_increment_gateway_submit_number_when_invalid_last_four_provided(
        ExistingPaymentProcessCommandHandler $handler
    ) {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getProtectedAttributeValue($handler, 'purchaseProcess');

        $this->assertEquals(1, $purchaseProcess->gatewaySubmitNumber());
    }

    /**
     * @test
     * @depends it_should_throw_invalid_payment_template_last_four_when_invalid_last_four_provided
     * @param ExistingPaymentProcessCommandHandler $handler Handler
     * @return void
     * @throws ReflectionException
     */
    public function it_should_change_purchase_process_state_to_processing_when_invalid_last_four_provided(
        ExistingPaymentProcessCommandHandler $handler
    ) {
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getProtectedAttributeValue($handler, 'purchaseProcess');

        $this->assertEquals(Processing::name(), $purchaseProcess->state()::name());
    }

    /**
     * @test
     * @return void
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws ApcNotLoadedException
     * @throws InvalidCurrency
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws UnsupportedPaymentMethodException
     * @throws UnknownBillerNameException
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_not_change_purchasee_process_state_to_processed_after_second_attempt_with_invalid_last_four()
    {
        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new InvalidPaymentTemplateLastFour());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $paymentTemplateAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $paymentTemplateTranslatingServiceMock = $this->getMockBuilder(PaymentTemplateTranslatingServiceImplementation::class)
            ->setConstructorArgs(
                [
                    $this->createMock(RetrievePaymentTemplatesServiceAdapter::class),
                    $this->createMock(RetrievePaymentTemplateServiceAdapter::class),
                    $paymentTemplateAdapter
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => RocketgateBiller::BILLER_NAME,
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        /** @var ExistingPaymentProcessCommandHandler $handler */
        $handler = $this->getMockForExistingPaymentProcessCommandHandler(
            $this->createMock(BillerMappingService::class),
            $processHandler,
            $paymentTemplateService
        );

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        // First submit with invalid last 4
        try {
            $handler->execute($command);
        } catch (\Exception $exception) {
            // Check exception is as expected
            $this->assertInstanceOf(InvalidPaymentTemplateLastFour::class, $exception);
        }

        // Second submit with invalid last 4
        try {
            $handler->execute($command);
        } catch (\Exception $exception) {
            // Check exception is as expected
            $this->assertInstanceOf(InvalidPaymentTemplateLastFour::class, $exception);
        }

        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $this->getProtectedAttributeValue($handler, 'purchaseProcess');

        $this->assertEquals(Processing::name(), $purchaseProcess->state()::name());
    }

    /**
     * @test
     * @return void
     * @throws ApcNotLoadedException
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidCommandException
     * @throws InvalidCurrency
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws InvalidZipCodeException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownBillerNameException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Exception
     */
    public function it_should_throw_retrieve_payment_template_exception_when_circuit_breaker_opened()
    {
        $this->expectException(RetrievePaymentTemplateException::class);

        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $paymentTemplateAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(
                        [
                            ValidatePaymentTemplateCommand::class => [
                                'circuitBreaker' => [
                                    'forceOpen' => true
                                ]
                            ]
                        ]
                    ),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $paymentTemplateTranslatingServiceMock = $this->getMockBuilder(PaymentTemplateTranslatingServiceImplementation::class)
            ->setConstructorArgs(
                [
                    $this->createMock(RetrievePaymentTemplatesServiceAdapter::class),
                    $this->createMock(RetrievePaymentTemplateServiceAdapter::class),
                    $paymentTemplateAdapter
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => RocketgateBiller::BILLER_NAME,
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        /** @var ExistingPaymentProcessCommandHandler $handler */
        $handler = $this->getMockForExistingPaymentProcessCommandHandler(
            $this->createMock(BillerMappingService::class),
            $processHandler,
            $paymentTemplateService
        );

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        $handler->execute($command);
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_duplicate_process_request_is_sent_for_processing()
    {
        $this->expectException(DuplicatedPurchaseProcessRequestException::class);

        $redisRepositoryMock = $this->createMock(RedisRepository::class);
        $redisRepositoryMock->method('retrievePurchaseStatus')->willReturn(Processing::name());

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('sessionId')->willReturn($this->faker->uuid);

        $handler = new ExistingPaymentProcessCommandHandler(
            $this->createMock(FraudService::class),
            $this->createMock(BillerMappingService::class),
            $this->createMock(LaravelBinRoutingServiceFactory::class),
            $this->createMock(CascadeTranslatingService::class),
            $this->createMock(PurchaseProcessHandler::class),
            $this->createMock(PurchaseService::class),
            $this->createMock(ProcessPurchaseDTOAssembler::class),
            $this->createMock(SiteRepositoryReadOnly::class),
            $this->createMock(PostbackService::class),
            $this->createMock(BILoggerService::class),
            $this->createMock(TransactionService::class),
            $this->createMock(PaymentTemplateService::class),
            $this->createMock(RetrieveFraudRecommendationForExistingCardOnProcess::class),
            $this->createMock(MemberProfileGatewayService::class),
            $this->createMock(EventIngestionService::class),
            $redisRepositoryMock,
            $this->createMock(CCForBlackListService::class)
        );

        $handler->execute($command);
    }

    /**
     * @test
     *
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws Exception
     * @throws IllegalStateTransitionException
     * @throws InvalidCurrency
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidUserInfoFirstName
     * @throws InvalidUserInfoLastName
     * @throws InvalidUserInfoPassword
     * @throws InvalidUserInfoPhoneNumber
     * @throws InvalidUserInfoUsername
     * @throws InvalidZipCodeException
     * @throws ReflectionException
     * @throws Throwable
     * @throws UnknownBillerNameException
     * @throws UnsupportedPaymentMethodException
     * @throws UnsupportedPaymentTypeException
     * @throws ValidationException
     */
    public function it_should_create_eis_event_if_last4_is_wrong(): void
    {
        $paymentTemplateService           = app()->make(PaymentTemplateService::class);
        $paymentTemplateServiceReflection = new ReflectionClass(get_class($paymentTemplateService));

        $adapterMock = $this->createMock(ValidatePaymentTemplateServiceAdapter::class);
        $adapterMock->method('validatePaymentTemplate')->willThrowException(new InvalidPaymentTemplateLastFour());

        /** @var MockObject|CircuitBreakerValidatePaymentTemplateServiceAdapter $cbAdapter */
        $paymentTemplateAdapter = $this->getMockBuilder(CircuitBreakerValidatePaymentTemplateServiceAdapter::class)
            ->setConstructorArgs(
                [
                    $this->getCircuitBreakerCommandFactory(),
                    $adapterMock
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $paymentTemplateTranslatingServiceMock = $this->getMockBuilder(PaymentTemplateTranslatingServiceImplementation::class)
            ->setConstructorArgs(
                [
                    $this->createMock(RetrievePaymentTemplatesServiceAdapter::class),
                    $this->createMock(RetrievePaymentTemplateServiceAdapter::class),
                    $paymentTemplateAdapter
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $property = $paymentTemplateServiceReflection->getProperty('paymentTemplateTranslatingService');
        $property->setAccessible(true);
        $property->setValue($paymentTemplateService, $paymentTemplateTranslatingServiceMock);

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = self::SUBSCRIPTION_ID;
        $mainItem['addonId']                         = self::ADDON_ID;
        $mainItem['bundleId']                        = self::BUNDLE_ID;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [$mainItem];

        $sessionPayload['paymentTemplateCollection'] = [
            [
                'templateId'                   => self::TEMPLATE_ID,
                'firstSix'                     => '481641',
                'expirationYear'               => '2099',
                'expirationMonth'              => '10',
                'lastUsedDate'                 => '2019-09-01 09:55:46',
                'createdAt'                    => '2019-09-01 09:55:46',
                'billerName'                   => RocketgateBiller::BILLER_NAME,
                'requiresIdentityVerification' => true,
                'identityVerificationMethod'   => 'last4'
            ]
        ];

        $sessionPayload['memberId'] = self::MEMBER_ID;
        $sessionPayload['userInfo']['email'] = 'testingEmail@test.mindgeek.com';

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $eventIngestionService = $this->createMock(EventIngestionService::class);
        $eventIngestionService->expects($this->once())
            ->method('queue')
            ->with($this->callback(function($event){
                return ($event instanceof FraudFailedPaymentTemplateValidation);
            }));


        /** @var ExistingPaymentProcessCommandHandler $handler */
        $handler = $this->getMockForExistingPaymentProcessCommandHandler(
            $this->createMock(BillerMappingService::class),
            $processHandler,
            $paymentTemplateService,
            $eventIngestionService
        );

        $command = $this->createProcessCommand(
            [
                'paymentTemplateId' => self::TEMPLATE_ID,
                'lastFour'          => self::LAST_FOUR
            ]
        );

        try {
            $handler->execute($command);
        } catch (\Exception $exception) {
            // Check exception is as expected
            $this->assertInstanceOf(InvalidPaymentTemplateLastFour::class, $exception);
        }
    }
}
