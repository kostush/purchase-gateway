<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseProcess;

use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ExistingPaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleRebillChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidPaymentInfoException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException;
use ProBillerNG\PurchaseGateway\Domain\Model\ExistingCCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItem;
use ProBillerNG\PurchaseGateway\Domain\Model\InitializedItemCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBillerFields;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\TaxInformation;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerMappingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector\DoctrineSiteProjectionRepository;
use Tests\UnitTestCase;

class ExistingPaymentProcessCommandHandlerTest extends UnitTestCase
{
    private const TEMPLATE_ID      = '4c22fba2-f883-11e8-8eb2-f2801f1b9fff';
    private const FIRST_SIX        = '123456';
    private const LAST_FOUR        = '1234';
    private const EXPIRATION_YEAR  = '2019';
    private const EXPIRATION_MONTH = '11';
    private const LAST_USED_DATE   = '2019-08-11 15:15:25';
    private const CREATED_AT       = '2019-08-11 15:15:25';
    private const POSTBACK_URL     = 'http://localhost';
    private const BILLER_NAME      = RocketgateBiller::BILLER_NAME;

    private const BILLER_FIELDS    = [
        'cardHash'           => 'cardHashString',
        'merchantCustomerId' => '123456789'
    ];

    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @var ExistingPaymentProcessCommandHandler
     */
    private $handler;

    /**
     * @var \ReflectionClass
     */
    private $reflection;

    /**
     * @return void
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
        $this->handler         = $this->createMock(ExistingPaymentProcessCommandHandler::class);
        $this->reflection      = new \ReflectionClass(ExistingPaymentProcessCommandHandler::class);
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function add_user_info_should_throw_exception_if_no_subscription_id_and_no_username(): void
    {
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function add_user_info_should_throw_exception_if_no_subscription_id_and_no_password(): void
    {
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function add_user_info_should_call_purchase_process_user_info_for_both_username_and_password(): void
    {
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
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function set_payment_info_should_call_purchase_process_with_existing_cc_payment_info(): void
    {
        $paymentInfo = ExistingCCPaymentInfo::create(
            self::BILLER_FIELDS['cardHash'],
            self::TEMPLATE_ID,
            null,
            ['cardHash' => 'cardHashString',
            'merchantCustomerId' => '123456789']
        );

        $this->purchaseProcess->expects($this->once())->method('setPaymentInfo')->with($paymentInfo);

        $setPaymentInfoMethod = $this->reflection->getMethod('setPaymentInfo');
        $setPaymentInfoMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $setPaymentInfoMethod->invoke($this->handler);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function set_biller_mapping_should_call_set_merchant_customer_id_when_rocketgate_biller_fields_returned(): void
    {
        $initializedItemCollection = new InitializedItemCollection();
        $initializedItem           = InitializedItem::create(
            SiteId::create(),
            BundleId::create(),
            AddonId::create(),
            $this->createMock(BundleRebillChargeInformation::class),
            $this->createMock(TaxInformation::class),
            false,
            false,
            $this->faker->uuid
        );
        $initializedItemCollection->add($initializedItem);

        $this->purchaseProcess->method('initializedItemCollection')->willReturn($initializedItemCollection);

        $billerMappingService   = $this->createMock(BillerMappingService::class);
        $billerMapping          = $this->createMock(BillerMapping::class);
        $rocketgateBillerFields = $this->createMock(RocketgateBillerFields::class);
        $billerMapping->method('billerFields')->willReturn($rocketgateBillerFields);

        $billerMappingService->method('retrieveBillerMapping')->willReturn($billerMapping);

        $rocketgateBillerFields->expects($this->once())->method('setMerchantCustomerId');

        $retrieveBillerMappingMethod = $this->reflection->getMethod('retrieveBillerMapping');
        $retrieveBillerMappingMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $siteRepoMock = $this->createMock(DoctrineSiteProjectionRepository::class);
        $siteRepoMock->method('findSite')->willReturn($this->createSite());

        $siteProperty = $this->reflection->getProperty('siteRepository');
        $siteProperty->setAccessible(true);
        $siteProperty->setValue($this->handler, $siteRepoMock);

        $billerMappingServiceProperty = $this->reflection->getProperty('billerMappingService');
        $billerMappingServiceProperty->setAccessible(true);
        $billerMappingServiceProperty->setValue($this->handler, $billerMappingService);

        $retrieveBillerMappingMethod->invokeArgs(
            $this->handler,
            [
                $this->createMock(Site::class),
                $this->createMock(RocketgateBiller::class)
            ]
        );
    }

    /**
     * @test
     * @throws \ReflectionException
     * @return void
     */
    public function it_should_throw_exception_when_set_payment_info_is_called_without_valid_card_hash(): void
    {
        $this->expectException(InvalidPaymentInfoException::class);

        $handler = $this->getMockBuilder(ExistingPaymentProcessCommandHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $purchaseProcess = $this->getMockBuilder(PurchaseProcess::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['retrieveSelectedPaymentTemplate', 'setPaymentInfo', 'paymentMethod'])
            ->getMock();
        $purchaseProcess->method('paymentMethod')->willReturn(null);

        $reflection = new \ReflectionClass(get_class($handler));
        $method     = $reflection->getMethod('setPaymentInfo');
        $method->setAccessible(true);

        $property = $reflection->getProperty('purchaseProcess');
        $property->setAccessible(true);
        $property->setValue($handler, $purchaseProcess);

        $paymentTemplate = $this->createMock(PaymentTemplate::class);
        $paymentTemplate->method('billerFields')->willReturn(['cardHash' => '']);
        $paymentTemplate->method('templateId')->willReturn($this->faker->uuid);

        $purchaseProcess->method('retrieveSelectedPaymentTemplate')->willReturn($paymentTemplate);

        $method->invoke($handler);
    }

    /**
     * @test
     *
     * @return void
     * @throws \ReflectionException
     */
    public function get_postback_url_should_return_value_from_purchase_process_session(): void
    {
        $this->purchaseProcess->method('postbackUrl')->willReturn(static::POSTBACK_URL);

        $processCommand = $this->createProcessCommand();

        $getPostbackUrlMethod = $this->reflection->getMethod('getPostbackUrl');
        $getPostbackUrlMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $result = $getPostbackUrlMethod->invoke($this->handler, $processCommand->site());

        $this->assertEquals(static::POSTBACK_URL, $result);
    }

    /**
     * @test
     *
     * @return void
     * @throws \ReflectionException
     */
    public function get_postback_url_should_return_value_from_site_if_no_postback_url_provided_in_the_payload(): void
    {
        $processCommand = $this->createProcessCommand();

        $getPostbackUrlMethod = $this->reflection->getMethod('getPostbackUrl');
        $getPostbackUrlMethod->setAccessible(true);

        $purchaseProcessProperty = $this->reflection->getProperty('purchaseProcess');
        $purchaseProcessProperty->setAccessible(true);
        $purchaseProcessProperty->setValue($this->handler, $this->purchaseProcess);

        $result = $getPostbackUrlMethod->invoke($this->handler, $processCommand->site());

        $this->assertEquals($processCommand->site()->postbackUrl(), $result);
    }
}
