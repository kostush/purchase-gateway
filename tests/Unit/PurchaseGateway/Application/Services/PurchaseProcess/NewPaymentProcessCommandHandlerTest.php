<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\BI\FraudPurchaseVelocity;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\Exceptions\MissingRedirectUrlException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\NewPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\Bin;
use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoZipCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\LastFour;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\NewCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\NonPCIPaymentFormData;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ReflectionClass;
use ReflectionException;
use Tests\UnitTestCase;
use Throwable;

class NewPaymentProcessCommandHandlerTest extends UnitTestCase
{
    private const TEMPLATE_ID      = '4c22fba2-f883-11e8-8eb2-f2801f1b9fff';
    private const FIRST_SIX        = '123456';
    private const LAST_FOUR        = '1234';
    private const EXPIRATION_YEAR  = '2019';
    private const EXPIRATION_MONTH = '11';
    private const LAST_USED_DATE   = '2019-08-11 15:15:25';
    private const CREATED_AT       = '2019-08-11 15:15:25';
    private const BILLER_NAME      = 'rocketgate';
    private const BILLER_FIELDS    = [
        'cardHash'           => 'cardHashString',
        'merchantCustomerId' => '123456789'
    ];

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var NewPaymentProcessCommandHandler
     */
    private $handler;

    /**
     * @var ReflectionClass
     */
    private $reflection;

    /**
     * @return void
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->handler         = $this->createMock(NewPaymentProcessCommandHandler::class);
        $this->reflection      = new ReflectionClass(NewPaymentProcessCommandHandler::class);
        $paymentTemplate       = PaymentTemplate::create(
            self::TEMPLATE_ID,
            self::FIRST_SIX,
            self::LAST_FOUR,
            self::EXPIRATION_YEAR,
            self::EXPIRATION_MONTH,
            self::LAST_USED_DATE,
            self::CREATED_AT,
            self::BILLER_NAME,
            self::BILLER_FIELDS
        );
        $paymentTemplate->setIsSelected(true);
        $this->purchaseProcess->method('retrieveSelectedPaymentTemplate')->willReturn($paymentTemplate);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function add_user_info_should_throw_exception_if_member_id_without_subscription_id_and_no_username(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn(null);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(
            [
                'username' => '',
                'password' => 'test1234'
            ]
        );

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoUsername::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function add_user_info_should_throw_exception_if_member_id_without_subscription_id_and_no_password(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn(null);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(
            [
                'username' => 'johnABCD',
                'password' => ''
            ]
        );

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoPassword::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws ReflectionException
     * @throws Throwable
     */
    public function check_return_url_should_throw_exception_if_empty_url_when_three_d_advised_and_supported(): void
    {
        $this->purchaseProcess->method('redirectUrl')->willReturn('');
        $this->purchaseProcess->method('isPending')->willReturn(true);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('checkReturnUrl');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(MissingRedirectUrlException::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, RocketgateBiller::BILLER_NAME);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function add_user_info_should_call_purchase_process_user_info_for_both_username_and_password(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn(null);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $this->purchaseProcess->expects($this->at(2))->method('userInfo');
        $processCommand = $this->createProcessCommand(
            [
                'username' => 'johnABCD',
                'password' => 'test1234'
            ]
        );

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function set_payment_info_should_call_purchase_process_with_new_cc_payment_info(): void
    {
        $paymentData = [
            'cvv'             => (string) $this->faker->numberBetween(100, 999),
            'ccNumber'        => $this->faker->creditCardNumber('Visa'),
            'expirationMonth' => '05',
            'expirationYear'  => '2099'
        ];

        $processCommand = $this->createProcessCommand(
            $paymentData
        );

        $newCCpaymentInfo = NewCCPaymentInfo::create(
            $paymentData['ccNumber'],
            $paymentData['cvv'],
            $paymentData['expirationMonth'],
            $paymentData['expirationYear'],
            null
        );

        $this->purchaseProcess->method('paymentInfo')->willReturn($newCCpaymentInfo);

        $this->purchaseProcess->expects($this->once())->method('setPaymentInfo')->with($newCCpaymentInfo);

        $setPaymentInfoMethod = $this->reflection->getMethod('setPaymentInfo');
        $setPaymentInfoMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $setPaymentInfoMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function it_should_generate_correct_non_pci_data_from_command(): void
    {
        /**
         * Create a new instance that skips the constructor
         */
        $commandHandler = (new class extends NewPaymentProcessCommandHandler {
            public function __construct(){}
        });

        $expectedEmail    = $this->faker->email;
        $firstName        = $this->faker->firstName;
        $lastName         = $this->faker->lastName;
        $countryCode      = $this->faker->countryCode;
        $zipCode          = $this->faker->postcode;
        $creditCardNumber = $this->faker->creditCardNumber;

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn($expectedEmail);
        $command->method('country')->willReturn($countryCode);
        $command->method('zip')->willReturn($zipCode);
        $command->method('firstName')->willReturn($firstName);
        $command->method('lastName')->willReturn($lastName);
        $command->method('ccNumber')->willReturn($creditCardNumber);

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('email')->willReturn(Email::create($this->faker->email));

        $pciFormData = $commandHandler->generateNonPCIDataFromCommand($command, $userInfo);

        $this->assertInstanceOf(NonPCIPaymentFormData::class, $pciFormData);
        $this->assertInstanceOf(Email::class, $pciFormData->email());
        $this->assertInstanceOf(CountryCode::class, $pciFormData->countryCode());
        $this->assertInstanceOf(Zip::class, $pciFormData->zip());

        $this->assertEquals($expectedEmail, (string) $pciFormData->email());
        $this->assertEquals($firstName, (string) $pciFormData->firstName());
        $this->assertEquals($lastName, (string) $pciFormData->lastName());
        $this->assertEquals($countryCode, (string) $pciFormData->countryCode());
        $this->assertEquals(Zip::create($zipCode),  $pciFormData->zip());
        $this->assertEquals(LastFour::createFromCCNumber($creditCardNumber), $pciFormData->lastFour());
        $this->assertEquals(Bin::createFromCCNumber($creditCardNumber), $pciFormData->bin());
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function it_should_fetch_first_and_last_name_from_user_info_when_not_in_command(): void
    {
        /**
         * Create a new instance that skips the constructor
         */
        $commandHandler = (new class extends NewPaymentProcessCommandHandler {
            public function __construct(){}
        });

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn($this->faker->email);
        $command->method('country')->willReturn($this->faker->countryCode);
        $command->method('zip')->willReturn($this->faker->postcode);
        $command->method('firstName')->willReturn('');
        $command->method('lastName')->willReturn('');
        $command->method('ccNumber')->willReturn($this->faker->creditCardNumber);

        $expectedFirstName = $this->faker->firstName;
        $expectedLastName  = $this->faker->lastName;

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('firstName')->willReturn(FirstName::create($expectedFirstName));
        $userInfo->method('lastName')->willReturn(LastName::create($expectedLastName));

        $result = $commandHandler->generateNonPCIDataFromCommand($command, $userInfo);

        $this->assertEquals($expectedFirstName, (string) $result->firstName());
        $this->assertEquals($expectedLastName, (string) $result->lastName());
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function it_should_return_empty_first_and_last_name_when_these_fields_are_not_in_command_nor_in_user_info(): void
    {
        /**
         * Create a new instance that skips the constructor
         */
        $commandHandler = (new class extends NewPaymentProcessCommandHandler {
            public function __construct(){}
        });

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn($this->faker->email);
        $command->method('country')->willReturn($this->faker->countryCode);
        $command->method('zip')->willReturn($this->faker->postcode);
        $command->method('firstName')->willReturn('');
        $command->method('lastName')->willReturn('');
        $command->method('ccNumber')->willReturn($this->faker->creditCardNumber);

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('firstName')->willReturn(null);
        $userInfo->method('lastName')->willReturn(null);

        $result = $commandHandler->generateNonPCIDataFromCommand($command, $userInfo);

        $this->assertEmpty($result->firstName());
        $this->assertEmpty($result->lastName());
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function it_should_fetch_the_email_from_user_info_when_not_in_command()
    {
        /**
         * Create a new instance that skips the constructor
         */
        $commandHandler = (new class extends NewPaymentProcessCommandHandler {
            public function __construct(){}
        });

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn('');
        $command->method('country')->willReturn('');
        $command->method('zip')->willReturn('');
        $command->method('ccNumber')->willReturn($this->faker->creditCardNumber);

        $expectedEmail = $this->faker->email;
        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('email')->willReturn(Email::create($expectedEmail));

        $result = $commandHandler->generateNonPCIDataFromCommand($command, $userInfo);
        $this->assertInstanceOf(NonPCIPaymentFormData::class, $result);
        $this->assertEquals($expectedEmail, (string) $result->email());
    }

    /**
     * @test
     * @throws Exception
     * @throws InvalidUserInfoCountry
     * @throws InvalidUserInfoEmail
     * @throws InvalidZipCodeException
     * @throws ValidationException
     */
    public function it_should_generate_correct_non_pci_data_from_command_with_invalid_user_fields()
    {
        /**
         * Create a new instance that skips the constructor
         */
        $commandHandler = (new class extends NewPaymentProcessCommandHandler {
            public function __construct(){}
        });

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('email')->willReturn('');
        $command->method('country')->willReturn('');
        $command->method('zip')->willReturn('');
        $command->method('ccNumber')->willReturn($this->faker->creditCardNumber);

        $userInfo = $this->createMock(UserInfo::class);
        $userInfo->method('email')->willReturn(null);

        $result = $commandHandler->generateNonPCIDataFromCommand($command, $userInfo);
        $this->assertInstanceOf(NonPCIPaymentFormData::class, $result);
        $this->assertNull($result->email());
        $this->assertNull($result->countryCode());
        $this->assertNull($result->zip());
    }

    /**
     * @test
     * @throws ReflectionException
     * @return void
     */
    public function it_should_throw_exception_when_set_payment_info_is_called_without_complete_data_into_command(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        $handler = $this->getMockBuilder(NewPaymentProcessCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setPaymentInfo', 'paymentMethod'])
            ->getMock();
        $purchaseProcess->method('paymentMethod')->willReturn(null);

        $reflection = new ReflectionClass(get_class($handler));
        $method     = $reflection->getMethod('setPaymentInfo');
        $method->setAccessible(true);

        $property = $reflection->getProperty('purchaseProcess');
        $property->setAccessible(true);
        $property->setValue($handler, $purchaseProcess);

        $command = $this->createMock(ProcessPurchaseCommand::class);
        $command->method('ccNumber')->willReturn('');
        $command->method('cvv')->willReturn('');
        $command->method('expirationMonth')->willReturn('');
        $command->method('expirationYear')->willReturn('');

        $method->invoke($handler, $command);
    }

    /**
     * @test
     */
    public function it_should_call_event_ingestion_when_successful_purchase()
    {
        $handler = $this->getMockBuilder(BasePaymentProcessCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass(get_class($handler));
        $method     = $reflection->getMethod('dispatchFraudVelocityEvent');
        $method->setAccessible(true);

        $velocityEvent = $this->getMockBuilder(FraudPurchaseVelocity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApproved','getType'])
            ->getMock();
        $velocityEvent->method('getType')->willReturn(FraudPurchaseVelocity::TYPE);
        /**
         * Expect is approved to be called
         */
        $velocityEvent->expects($this->once())->method('isApproved')->willReturn(true);

        $eventIngestionService = $this->getMockBuilder(EventIngestionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['queue'])
            ->getMock();

        $paymentoInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);

        /**
         * Expect queue to be called
         */
        $eventIngestionService->expects($this->exactly(2))->method('queue');

        $method->invoke($handler, $eventIngestionService, $velocityEvent, $paymentoInfo);
    }

    /**
     * @test
     */
    public function it_should_not_call_event_ingestion_when_aborted_or_declined_purchase()
    {

        $handler = $this->getMockBuilder(BasePaymentProcessCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $reflection = new ReflectionClass(get_class($handler));
        $method     = $reflection->getMethod('dispatchFraudVelocityEvent');
        $method->setAccessible(true);

        $velocityEvent = $this->getMockBuilder(FraudPurchaseVelocity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApproved', 'getType'])
            ->getMock();
        $velocityEvent->method('getType')->willReturn(FraudPurchaseVelocity::TYPE);
        /**
         * Expect is approved to be called
         */
        $velocityEvent->expects($this->once())->method('isApproved')->willReturn(false);

        $eventIngestionService = $this->getMockBuilder(EventIngestionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['queue'])
            ->getMock();

        $paymentoInfo = CCPaymentInfo::build(CCPaymentInfo::PAYMENT_TYPE, null);

        $method->invoke($handler, $eventIngestionService, $velocityEvent, $paymentoInfo);
    }


    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_first_name_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(
            [
                'firstName' => "te\tst"
            ]
        );

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoFirstName::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_last_name_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['lastName' => "te\tst"]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoLastName::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_username_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['username' => ""]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoUsername::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_email_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['email' => ""]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoEmail::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }


    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_password_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['password' => ""]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoPassword::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_zip_code_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['zip' => "!@#$%^&"]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidZipCodeException::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_invalid_country_code_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['country' => "!@"]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoCountry::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }

    /**
     * @test
     * @return void
     * @throws Exception
     * @throws ReflectionException
     * @throws Throwable
     */
    public function it_should_throw_exception_when_empty_country_code_provided_on_user_info_if_member_id_with_subscription_id(): void
    {
        $this->purchaseProcess->method('memberId')->willReturn($this->faker->uuid);
        $this->purchaseProcess->method('mainPurchaseSubscriptionId')->willReturn($this->faker->uuid);

        $this->purchaseProcess->method('cascade')->willReturn(
            Cascade::create(BillerCollection::buildBillerCollection([new RocketgateBiller()]))
        );

        $processCommand = $this->createProcessCommand(['country' => ""]);

        $addUserInfoToPurchaseProcessMethod = $this->reflection->getMethod('addUserInfoToPurchaseProcess');
        $addUserInfoToPurchaseProcessMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $this->expectException(InvalidUserInfoCountry::class);

        $addUserInfoToPurchaseProcessMethod->invoke($this->handler, $processCommand);
    }
}
