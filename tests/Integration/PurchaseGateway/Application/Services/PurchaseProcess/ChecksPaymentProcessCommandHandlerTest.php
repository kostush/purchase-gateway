<?php

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendation;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudRecommendationCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\FraudRecommendationAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\FraudRecommendation\RetrieveFraudRecommendationForNewPaymentOnProcessTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\NuDataService;
use Tests\IntegrationTestCase;

class ChecksPaymentProcessCommandHandlerTest extends IntegrationTestCase
{
    private const SUBSCRIPTION_ID = '6a71e3b9-65a0-34da-8ebd-031f916e971e';

    private const MEMBER_ID       = '70901d6d-1621-4466-bd46-2f03ac455ad5';

    private const BUNDLE_ID       = '5fd44440-2956-11e9-b210-d663bd873d93';

    private const ADDON_ID        = '670af402-2956-11e9-b210-d663bd873d93';

    /**
     * @var string
     */
    private $memberId;

    /**
     * @var string
     */
    private $bundleId;

    /**
     * @var string
     */
    private $addonId;

    /**
     * @var string
     */
    private $subscriptionId;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->memberId       = self::MEMBER_ID;
        $this->bundleId       = self::BUNDLE_ID;
        $this->addonId        = self::ADDON_ID;
        $this->subscriptionId = self::SUBSCRIPTION_ID;
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_call_fraud_using_correct_params()
    {
        // Creates a biller mapping
        $billerMappingServiceMock = $this->buildBillerMappingServiceMock();

        // Creates a session payload
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        // Make sure that is not a crossSale
        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        // Make sure it is a Cheque payment type
        $sessionPayload['paymentType'] = ChequePaymentInfo::PAYMENT_TYPE;

        // creates a process handler and load the session
        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        // mocks site
        $site                    = $this->buildSiteMock();
        $siteRepository          = $this->buildSiteRepository($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        // Creates the command with check info
        $command = $this->createProcessCommand(
            [
                'site'          => $site,
                'payment'       => [
                    "checkInformation" => [
                        "routingNumber"       => "999999999",
                        "accountNumber"       => "112233",
                        "savingAccount"       => false,
                        "socialSecurityLast4" => "5233",
                        "label"               => "testLabel"
                    ]
                ],
                'zip'           => '123345',
                'routingNumber' => '999999999',
                'accountNumber' => '112233',
                "paymentType"   => "checks",
                "paymentMethod" => "checks",
                "bin"           => '',
                'lastFour'      => ''
            ]
        );

        // Creates a mock fraud adapter to be
        $fraudRecommendationAdapter = $this->createMock(FraudRecommendationAdapter::class);
        $fraudRecommendationAdapter->expects($this->exactly(1))
            ->method('retrieve')
            ->with(
                $site->businessGroupId()->value(),
                $site->id(),
                'ProcessCustomer',
                [
                    "totalAmount"   => ["1"],
                    "routingNumber" => ["999999999"],
                    "firstName"     => [$command->firstName()],
                    "lastName"      => [$command->lastName()],
                    "email"         => [$command->email()],
                    "address"       => [null],
                    "city"          => [null],
                    "state"         => [null],
                    "zipCode"       => ['123345'],
                    "countryCode"   => [$command->country()],
                    "domain"        => ["EPS.mindgeek.com"],
                    'siteId'        => [$site->id()]
                ],
                $sessionPayload['sessionId'],
                []
            )
            ->willReturn(new FraudRecommendationCollection([FraudRecommendation::create(FraudRecommendation::DEFAULT_CODE, FraudRecommendation::DEFAULT_SEVERITY, FraudRecommendation::DEFAULT_MESSAGE)]));

        $retrieveFraudRecommendation = new RetrieveFraudRecommendationForNewChequeOnProcessTranslatingService(
            $fraudRecommendationAdapter
        );
        $fraudAdviceServiceFactory   = $this->createMock(FraudAdviceService\FraudRecommendationServiceFactory::class);

        $fraudAdviceServiceFactory->method('buildFraudRecommendationForPaymentOnProcess')->willReturn(
            $retrieveFraudRecommendation
        );

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $billerMappingServiceMock,
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    app()->make(PurchaseService::class),
                    $httpCommandDTOAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    app()->make(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $fraudAdviceServiceFactory
                ]
            )
            ->onlyMethods(
                [
                    'retrieveRoutingCodes',
                    'shipBiProcessedPurchaseEvent'
                ]
            )
            ->getMock();

        /**
         * @var ProcessPurchaseHttpDTO $dto
         */
        $dto = $handler->execute($command);
        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        $this->assertTrue($dto->jsonSerialize()['success']);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidCurrency
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentMethodException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException
     */
    public function it_should_return_a_captcha_response()
    {
        // Creates a biller mapping
        $billerMappingServiceMock = $this->buildBillerMappingServiceMock();

        // Creates a session payload
        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        // Make sure that is not a crossSale
        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        // Make sure it is a Cheque payment type
        $sessionPayload['paymentType'] = ChequePaymentInfo::PAYMENT_TYPE;

        // creates a process handler and load the session
        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        // mocks site
        $site                    = $this->buildSiteMock();
        $siteRepository          = $this->buildSiteRepository($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

        // Creates the command with check info
        $command = $this->createProcessCommand(
            [
                'site'          => $site,
                'payment'       => [
                    "checkInformation" => [
                        "routingNumber"       => "999999999",
                        "accountNumber"       => "112233",
                        "savingAccount"       => false,
                        "socialSecurityLast4" => "5233",
                        "label"               => "testLabel"
                    ]
                ],
                'zip'           => '123345',
                'routingNumber' => '999999999',
                'accountNumber' => '112233',
                "paymentType"   => "checks",
                "paymentMethod" => "checks",
                "bin"           => '',
                'lastFour'      => ''
            ]
        );

        $fraudRecommendationServiceFactory  = new FraudRecommendationServiceFactory();

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $billerMappingServiceMock,
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    app()->make(PurchaseService::class),
                    $httpCommandDTOAssembler,
                    $siteRepository,
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    app()->make(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $fraudRecommendationServiceFactory
                ]
            )
            ->onlyMethods(
                [
                    'retrieveRoutingCodes',
                    'shipBiProcessedPurchaseEvent'
                ]
            )
            ->getMock();

        /**
         * @var ProcessPurchaseHttpDTO $dto
         */
        $dto = $handler->execute($command);

        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        $this->assertTrue($dto->jsonSerialize()['fraudAdvice']['captcha']);
    }
    /**
     * Boilerplate support methods
     */
    /**
     * @return BillerMapping
     */
    private function buildBillerMappingMock(): BillerMapping
    {
        $rocketgateBillerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_2'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
            '2037',
            'sharedSecret',
            false
        );

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            RocketgateBiller::BILLER_NAME,
            $rocketgateBillerFields
        );

        return $billerMapping;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|BillerMappingService
     */
    private function buildBillerMappingServiceMock()
    {
        $billerMapping = $this->buildBillerMappingMock();

        $billerMappingServiceMock = $this->createMock(BillerMappingService::class);
        $billerMappingServiceMock->method('retrieveBillerMapping')->willReturn(
            $billerMapping
        );

        return $billerMappingServiceMock;
    }

    /**
     * @return \ProBillerNG\PurchaseGateway\Domain\Model\Site
     * @throws \Exception
     */
    private function buildSiteMock(): \ProBillerNG\PurchaseGateway\Domain\Model\Site
    {
        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(Service::create(ServicesList::FRAUD, true));
        $site = $this->createSite(false, false, $serviceCollection);

        return $site;
    }

    /**
     * @param \ProBillerNG\PurchaseGateway\Domain\Model\Site $site
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|SiteRepositoryReadOnly
     */
    private function buildSiteRepository(\ProBillerNG\PurchaseGateway\Domain\Model\Site $site)
    {
        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $siteRepository->method('findSite')->willReturn($site);

        return $siteRepository;
    }
}
