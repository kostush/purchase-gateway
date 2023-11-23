<?php
declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\HttpCommandDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Exceptions\BillerMappingException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Services\CryptService;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AtlasFields;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
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
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcessState\Processing;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerNameException;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\JsonWebTokenGenerator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\NuDataService;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Tests\IntegrationTestCase;
use Throwable;

class NewPaymentProcessCommandHandlerTest extends IntegrationTestCase
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
     * @return    array
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
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
     * @throws ValidationException
     * @throws UnknownBillerNameException
     */
    public function it_should_return_a_purchase_process_dto(): array
    {
        $rocketgateBillerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_2'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
            '2037',
            'sharedSecret',
            true
        );

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            RocketgateBiller::BILLER_NAME,
            $rocketgateBillerFields
        );

        $billerMappingServiceMock = $this->createMock(BillerMappingService::class);
        $billerMappingServiceMock->method('retrieveBillerMapping')->willReturn(
            $billerMapping
        );

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                                    = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId']                  = $this->subscriptionId;
        $mainItem['addonId']                         = $this->addonId;
        $mainItem['bundleId']                        = $this->bundleId;
        $mainItem['isCrossSale']                     = false;
        $sessionPayload['initializedItemCollection'] = [
            $mainItem
        ];

        $sessionPayload['memberId'] = $this->memberId;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));

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
                    $this->createMock(FraudRecommendationServiceFactory::class)
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
                'expirationMonth' => '05',
                'expirationYear'  => '2099',
                'ccNumber'        => $this->faker->creditCardNumber('MasterCard'),
                'cvv'             => (string) $this->faker->numberBetween(100, 999)
            ]
        );

        $dto = $handler->execute($command);
        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);

        return $dto->jsonSerialize();
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
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
     * @depends it_should_return_a_purchase_process_dto
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
     * @depends it_should_return_a_purchase_process_dto
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
     * @depends it_should_return_a_purchase_process_dto
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
     * @depends it_should_return_a_purchase_process_dto
     *
     * @param array $response Response.
     *
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_memberId_key(array $response): void
    {
        $this->assertEquals($this->memberId, $response['memberId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_bundleId_key(array $response): void
    {
        $this->assertEquals($this->bundleId, $response['bundleId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_addonId_key(array $response): void
    {
        $this->assertEquals($this->addonId, $response['addonId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_correct_subscriptionId_key(array $response): void
    {
        $this->assertEquals($this->subscriptionId, $response['subscriptionId']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_transactionId_key(array $response): void
    {
        $this->assertArrayHasKey('transactionId', $response);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_billerName_key(array $response): void
    {
        $this->assertArrayHasKey('billerName', $response);
        $this->assertEquals(RocketgateBiller::BILLER_NAME, $response['billerName']);
    }

    /**
     * @test
     * @depends it_should_return_a_purchase_process_dto
     * @param array $response Response.
     * @return void
     */
    public function process_purchase_dto_should_contain_digest_key(array $response): void
    {
        $this->assertArrayHasKey('digest', $response);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     */
    public function set_biller_mapping_should_throw_exception_when_api_communication_is_down(): void
    {
        $this->expectException(BillerMappingException::class);

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initializedItemCollection', 'cascade', 'sessionId', 'currency'])
            ->getMock();

        $itemCollection = new InitializedItemCollection();
        $itemCollection->add($this->createMock(InitializedItem::class));

        $purchaseProcess->method('initializedItemCollection')->willReturn($itemCollection);
        $purchaseProcess->method('cascade')->willReturn($this->createMock(Cascade::class));
        $purchaseProcess->method('sessionId')->willReturn($this->createMock(SessionId::class));
        $purchaseProcess->method('currency')->willReturn($this->createMock(CurrencyCode::class));

        $reflection = new ReflectionClass(get_class($handler));
        $method     = $reflection->getMethod('retrieveBillerMapping');
        $method->setAccessible(true);

        $property = $reflection->getProperty('purchaseProcess');
        $property->setAccessible(true);
        $property->setValue($handler, $purchaseProcess);


        $billerMapping = $this->createMock(BillerMappingService::class);
        $billerMapping->method('retrieveBillerMapping')->willThrowException(new \Exception('test exception'));

        $property = $reflection->getProperty('billerMappingService');
        $property->setAccessible(true);
        $property->setValue($handler, $billerMapping);

        $siteRepoMock = $this->createMock(DoctrineSiteProjectionRepository::class);
        $siteRepoMock->method('findSite')->willReturn($this->createSite());

        $purchaseProcessProperty = $reflection->getProperty('siteRepository');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($handler, $siteRepoMock);

        $method->invokeArgs(
            $handler,
            [
                $this->createMock(Site::class),
                $this->createMock(RocketgateBiller::class)
            ]
        );
    }

    /**
     * @test
     * @return void
     */
    public function it_should_change_state_to_pending_if_next_biller_is_third_party_and_state_is_valid(): void
    {
        $this->markTestIncomplete();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_not_change_the_state_if_next_biller_is_not_third_party_and_state_is_valid(): void
    {
        $this->markTestIncomplete();
    }


    /**
     * @test
     * @return void
     *
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws ReflectionException
     */
    public function it_should_add_all_required_user_info_to_a_new_payment_purchase_process(): void
    {
        // GIVEN
        $purchaseProcess = PurchaseProcess::create(
            $this->createMock(SessionId::class),
            $this->createMock(AtlasFields::class),
            1,
            UserInfo::create(CountryCode::create('CA'), Ip::create('127.0.0.1')),
            $this->createMock(PaymentInfo::class),
            $this->createMock(InitializedItemCollection::class),
            '',
            '',
            CurrencyCode::CAD(),
            '',
            '',
            ''
        );

        $newPaymentProcessCommandHandler = new NewPaymentProcessCommandHandler(
            $this->createMock(FraudService::class),
            $this->createMock(NuDataService::class),
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
            $this->createMock(EventIngestionService::class),
            $this->createMock(RedisRepository::class),
            $this->createMock(CCForBlackListTranslatingService::class),
            $this->createMock(FraudRecommendationServiceFactory::class)
        );

        $processPurchaseCommand = new ProcessPurchaseCommand(
            $this->createMock(Site::class),
            'johndoe',
            'strong-password',
            'adduserinfo@test.mindgeek.com',
            '',
            'ZIP123',
            '',
            '',
            '',
            'John',
            'Doe',
            '7777 Decarie',
            [],
            'New York',
            'NY',
            'US',
            '',
            '',
            '',
            '',
            ''
        );

        $reflectionClass    = new ReflectionClass(NewPaymentProcessCommandHandler::class);
        $reflectionProperty = $reflectionClass->getProperty('purchaseProcess');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($newPaymentProcessCommandHandler, $purchaseProcess);

        // WHEN
        $reflectionMethod = new ReflectionMethod(NewPaymentProcessCommandHandler::class,
            'addUserInfoToPurchaseProcess');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($newPaymentProcessCommandHandler, $processPurchaseCommand);

        // THEN
        $this->assertEquals('US', $purchaseProcess->userInfo()->countryCode());
        $this->assertEquals('adduserinfo@test.mindgeek.com', $purchaseProcess->userInfo()->email());
        $this->assertEquals('John', (string) $purchaseProcess->userInfo()->firstName());
        $this->assertEquals('Doe', (string) $purchaseProcess->userInfo()->lastName());
        $this->assertEquals('johndoe', (string) $purchaseProcess->userInfo()->username());
        $this->assertEquals('strong-password', (string) $purchaseProcess->userInfo()->password());
        $this->assertEquals('ZIP123', (string) $purchaseProcess->userInfo()->zipCode());
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws Throwable
     */
    public function it_should_throw_exception_when_duplicate_process_request_is_sent_for_processing()
    {
        $this->expectException(DuplicatedPurchaseProcessRequestException::class);

        $redisRepositoryMock = $this->createMock(RedisRepository::class);
        $redisRepositoryMock->method('retrievePurchaseStatus')->willReturn(Processing::name());

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('sessionId')->willReturn($this->faker->uuid);

        $handler = new NewPaymentProcessCommandHandler(
            $this->createMock(FraudService::class),
            $this->createMock(\ProBillerNG\PurchaseGateway\Domain\Services\NuDataService::class),
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
            $this->createMock(EventIngestionService::class),
            $redisRepositoryMock,
            $this->createMock(CCForBlackListService::class),
            $this->createMock(FraudRecommendationServiceFactory::class)
        );

        $handler->execute($command);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws InvalidIpException
     * @throws InvalidUserInfoCountry
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws IllegalStateTransitionException
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
     * @throws ValidationException
     * @throws UnknownBillerNameException
     */
    public function it_should_block_cross_sales_made_for_sites_black_listed_cards(): void
    {
        $rocketgateBillerFields = RocketgateBillerFields::create(
            $_ENV['ROCKETGATE_MERCHANT_ID_2'],
            $_ENV['ROCKETGATE_MERCHANT_PASSWORD_2'],
            '2037',
            'sharedSecret',
            true
        );

        $billerMapping = BillerMapping::create(
            SiteId::createFromString($this->faker->uuid),
            BusinessGroupId::createFromString($this->faker->uuid),
            CurrencyCode::USD,
            RocketgateBiller::BILLER_NAME,
            $rocketgateBillerFields
        );

        $billerMappingServiceMock = $this->createMock(BillerMappingService::class);
        $billerMappingServiceMock->method('retrieveBillerMapping')->willReturn(
            $billerMapping
        );

        $sessionPayload = json_decode($this->latestVersionSessionPayload(), true);

        $mainItem                   = $sessionPayload['initializedItemCollection'][0];
        $mainItem['subscriptionId'] = $this->subscriptionId;
        $mainItem['addonId']        = $this->addonId;
        $mainItem['bundleId']       = $this->bundleId;
        $mainItem['isCrossSale']    = false;

        $itemIdForBlackListedForMasterCard = $this->faker->uuid;
        $crossSaleBlackListedForMasterCard = [
            "itemId"              => $itemIdForBlackListedForMasterCard,
            "bundleId"            => "4475820e-2956-11e9-b210-d663bd873d93",
            "addonId"             => "4e1b0d7e-2956-11e9-b210-d663bd873d93",
            "siteId"              => "299f959d-cf3d-11e9-8c91-0cc47a283dd2",
            "initialAmount"       => 1,
            "initialDays"         => 3,
            "isTrial"             => true,
            "tax"                 => [],
            "isCrossSale"         => true,
            "isCrossSaleSelected" => false,
        ];

        $itemIdForBlackListedForVisaCard             = $this->faker->uuid;
        $crossSaleBlackListedForVisa                 = [
            "itemId"              => $itemIdForBlackListedForVisaCard,
            "bundleId"            => "a0a3aa08-f106-410d-ae6e-34f92d98f09b",
            "addonId"             => "94dd40cf-3de3-4c5b-a214-1cf6bd580683",
            "siteId"              => "b8e9f9d4-bd17-47e3-ac9c-04261a0c1904",
            "initialAmount"       => 1,
            "initialDays"         => 3,
            "isTrial"             => true,
            "tax"                 => [],
            "isCrossSale"         => true,
            "isCrossSaleSelected" => false,
        ];
        $sessionPayload['initializedItemCollection'] = [
            $mainItem,
            $crossSaleBlackListedForMasterCard,
            $crossSaleBlackListedForVisa,
        ];

        $sessionPayload['memberId'] = $this->memberId;

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore($sessionPayload)
        );

        $siteRepository = $this->createMock(SiteRepositoryReadOnly::class);
        $site           = $this->createSite();
        $siteRepository->method('findSite')->willReturn($site);
        $tokenGenerator          = new JsonWebTokenGenerator();
        $httpCommandDTOAssembler = new HttpCommandDTOAssembler($tokenGenerator, $site, app(CryptService::class));
        $purchaseService         = app()->make(PurchaseService::class);

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
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
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'retrieveRoutingCodes',
                    'shipBiProcessedPurchaseEvent',
                ]
            )
            ->getMock();

        $command = $this->createProcessCommand(
            [
                'expirationMonth' => '05',
                'expirationYear'  => '2099',
                'ccNumber'        => $this->faker->creditCardNumber('MasterCard'),
                'cvv'             => (string) $this->faker->numberBetween(100, 999),
                'crossSales'      => [
                    [
                        "bundleId"    => "4475820e-2956-11e9-b210-d663bd873d93",
                        "addonId"     => "4e1b0d7e-2956-11e9-b210-d663bd873d93",
                        "siteId"      => "299f959d-cf3d-11e9-8c91-0cc47a283dd2", // Blacklisted for MasterCard
                        "amount"      => 1,
                        "initialDays" => 3,
                        "isTrial"     => true,
                        "tax"         => [],
                    ],
                    [
                        "bundleId"    => "a0a3aa08-f106-410d-ae6e-34f92d98f09b",
                        "addonId"     => "94dd40cf-3de3-4c5b-a214-1cf6bd580683",
                        "siteId"      => "b8e9f9d4-bd17-47e3-ac9c-04261a0c1904", // Blacklisted for Visa
                        "amount"      => 1,
                        "initialDays" => 3,
                        "isTrial"     => true,
                        "tax"         => [],
                    ],
                ],
            ]
        );

        $dto = $handler->execute($command);

        $resultedCrossSales = $dto->jsonSerialize()['crossSells'];

        $this->assertInstanceOf(ProcessPurchaseHttpDTO::class, $dto);
        $this->assertCount(1, $resultedCrossSales);
        $this->assertStringNotContainsStringIgnoringCase($itemIdForBlackListedForMasterCard,
            print_r($resultedCrossSales[0], true));
        $this->assertStringContainsStringIgnoringCase($itemIdForBlackListedForVisaCard,
            print_r($resultedCrossSales[0], true));
    }
}
