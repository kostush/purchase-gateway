<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NewPaymentProcessCommandHandler\PurchaseProcessCommandHandler;

use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\DTO\Purchase\Process\ProcessPurchaseGeneralHttpDTO;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Domain\Services\BinRoutingService;
use ProBillerNG\PurchaseGateway\Domain\Services\CascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudAdviceService\FraudRecommendationServiceFactory;
use ProBillerNG\PurchaseGateway\Domain\Services\FraudService;
use ProBillerNG\PurchaseGateway\Domain\Services\NuDataService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\InMemory\RedisRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\LaravelBinRoutingServiceFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\CCForBlackList\CCForBlackListTranslatingService;
use ReflectionClass;
use Tests\UnitTestCase;

class AddUserInfoToPurchaseProcessTest extends UnitTestCase
{
    /**
     * @var NewPaymentProcessCommandHandler
     */
    private $handler;

    /**
     * @var ProcessPurchaseCommand
     */
    private $command;

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     * @return UserInfo
     */
    public function it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process(): UserInfo
    {
        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true))
        );

        $dtoAssembler = $this->getMockBuilder(ProcessPurchaseDTOAssembler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['assemble'])
            ->getMock();
        $dtoAssembler->method('assemble')->willReturn($this->createMock(ProcessPurchaseGeneralHttpDTO::class));

        $this->handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(NuDataService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(LaravelBinRoutingServiceFactory::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseService::class),
                    $dtoAssembler,
                    $this->createMock(SiteRepositoryReadOnly::class),
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(CCForBlackListTranslatingService::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'shipBiProcessedPurchaseEvent',
                    'buildDtoPostback',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();
        $this->command = $this->createMock(ProcessPurchaseCommand::class);
        $this->command->method('address')->willReturn($this->faker->address);
        $this->command->method('ccNumber')->willReturn($this->faker->creditCardNumber('MasterCard'));
        $this->command->method('state')->willReturn('QC');
        $this->command->method('country')->willReturn('CA');
        $this->command->method('city')->willReturn($this->faker->city);
        $this->command->method('email')->willReturn($this->faker->email);
        $this->command->method('firstName')->willReturn($this->faker->firstName);
        $this->command->method('lastName')->willReturn($this->faker->lastName);
        $this->command->method('password')->willReturn($this->faker->password(8, 16));
        $this->command->method('phoneNumber')->willReturn((string) $this->faker->numberBetween(10000, 99999));
        $this->command->method('username')->willReturn('username');
        $this->command->method('zip')->willReturn(
            (string) $this->faker->numberBetween(10000, 99999)
        );
        $this->command->method('site')->willReturn($this->createSite());

        $reflection                                = new ReflectionClass(NewPaymentProcessCommandHandler::class);
        $newPaymentProcessCommandHandlerReflection = $reflection->getProperty('purchase');
        $newPaymentProcessCommandHandlerReflection->setAccessible(true);
        $newPaymentProcessCommandHandlerReflection->setValue($this->handler, $this->createMock(Purchase::class));

        $this->handler->execute($this->command);
        $reflection = new ReflectionClass($this->handler);

        $newPaymentProcessCommandHandlerReflection = $reflection->getProperty('purchaseProcess');
        $newPaymentProcessCommandHandlerReflection->setAccessible(true);
        /** @var PurchaseProcess $purchaseProcess */
        $purchaseProcess = $newPaymentProcessCommandHandlerReflection->getValue($this->handler);

        $this->assertInstanceOf(Username::class, $purchaseProcess->userInfo()->username());
        return $purchaseProcess->userInfo();
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_password_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(Password::class, $userInfo->password());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_first_name_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(FirstName::class, $userInfo->firstName());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_last_name_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(LastName::class, $userInfo->lastName());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_email_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(Email::class, $userInfo->email());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_zip_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(Zip::class, $userInfo->zipCode());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_city_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertIsString($userInfo->city());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_state_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertIsString($userInfo->state());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_country_code_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(CountryCode::class, $userInfo->countryCode());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_phone_number_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(PhoneNumber::class, $userInfo->phoneNumber());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_address_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertIsString($userInfo->address());
        $this->assertInstanceOf(Ip::class, $userInfo->ipAddress());
    }

    /**
     * @test
     * @param UserInfo $userInfo The user info
     * @depends it_should_add_username_to_the_user_info_vo_on_the_the_purchase_process
     * @return void
     */
    public function it_should_add_ip_to_the_user_info_vo_on_the_the_purchase_process(UserInfo $userInfo): void
    {
        $this->assertInstanceOf(Ip::class, $userInfo->ipAddress());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\UnsupportedPaymentTypeException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \ProBillerNG\PurchaseGateway\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function it_should_throw_exception_when_invalid_info_is_supplied(): void
    {
        $this->expectException(\Exception::class);

        $purchaseProcessMock = $this->createMock(PurchaseProcess::class);
        $purchaseProcessMock->method('isProcessed')->willReturn(true);

        $processHandler = $this->createMock(PurchaseProcessHandler::class);
        $processHandler->method('load')->willReturn(
            PurchaseProcess::restore(json_decode($this->latestVersionSessionPayload(), true))
        );

        $this->handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(FraudService::class),
                    $this->createMock(BillerMappingService::class),
                    $this->createMock(BinRoutingService::class),
                    $this->createMock(CascadeTranslatingService::class),
                    $processHandler,
                    $this->createMock(PurchaseRepository::class),
                    $this->createMock(ProcessPurchaseDTOAssembler::class),
                    $this->createMock(SiteRepositoryReadOnly::class),
                    $this->createMock(PostbackService::class),
                    $this->createMock(BILoggerService::class),
                    $this->createMock(TransactionService::class),
                    $this->createMock(EventIngestionService::class),
                    $this->createMock(RedisRepository::class),
                    $this->createMock(FraudRecommendationServiceFactory::class)
                ]
            )
            ->onlyMethods(
                [
                    'checkIfPurchaseHasBeenAlreadyProcessed',
                    'checkUserInput',
                    'setPaymentInfo',
                    'retrieveBillerMapping',
                    'retrieveRoutingCodes',
                    'checkSelectedCrossSales',
                    'createPurchaseEntity',
                    'setIsNSFValueForEachInitializedItem'
                ]
            )
            ->getMock();
        $this->command = $this->createMock(ProcessPurchaseCommand::class);
        $this->command->method('phoneNumber')->willReturn('+1 (549) 8162066');
        $this->command->method('username')->willReturn($this->faker->userName);
        $this->command->method('email')->willReturn($this->faker->email);
        $this->command->method('ccNumber')->willReturn($this->faker->creditCardNumber('MasterCard'));
        $this->command->method('zip')->willReturn(
            Zip::create((string) $this->faker->numberBetween(10000, 99999))
        );

        $reflection = new ReflectionClass(NewPaymentProcessCommandHandler::class);
        $purchase   = $reflection->getProperty('purchase');
        $purchase->setAccessible(true);
        $purchase->setValue($this->handler, $this->createMock(Purchase::class));

        $this->handler->execute($this->command);
    }
}
