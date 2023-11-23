<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\PurchaseProcess;

use Illuminate\Support\Facades\Config;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\BI\Event\BaseEvent;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseInitialized;
use ProBillerNG\PurchaseGateway\Application\BI\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Application\DTO\Complete\CompleteThreeDDTOAssembler;
use ProBillerNG\PurchaseGateway\Application\Services\Complete\CompleteThreeDCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\BasePaymentProcessCommandHandler;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcess\ProcessPurchaseCommand;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler as SessionHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\BusinessGroupId;
use ProBillerNG\PurchaseGateway\Domain\Model\CCPaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\ChequePaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\KeyId;
use ProBillerNG\PurchaseGateway\Domain\Model\OtherPaymentTypeInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKey;
use ProBillerNG\PurchaseGateway\Domain\Model\PublicKeyCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\CCForBlackListService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentInfoFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\PostbackService;
use ProBillerNG\PurchaseGateway\Domain\Services\PurchaseService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ReflectionClass;
use Tests\UnitTestCase;

/**
 * Class BasePaymentProcessCommandHandlerTest
 * @package Tests\Unit\PurchaseGateway\Application\Services\PurchaseProcess
 * @group   event-ingestion
 */
class BasePaymentProcessCommandHandlerTest extends UnitTestCase
{
    /**
     * @var array
     */
    private $purchaseProcessedAsArray;

    /**
     * @var Site
     */
    protected $randomSite;

    public function setUp(): void
    {
        parent::setUp();
        $this->purchaseProcessedAsArray = [
            'memberInfo'            => [
                'email'       => $this->faker->email,
                'username'    => $this->faker->userName,
                'firstName'   => $this->faker->firstName,
                'lastName'    => $this->faker->lastName,
                'countryCode' => $this->faker->countryCode,
                'zipCode'     => $this->faker->postcode,
                'address'     => $this->faker->address,
                'city'        => $this->faker->city,
            ],
            'payment'               => [
                'first6' => $this->faker->numerify('######'),
                'last4'  => $this->faker->numerify('####'),
            ],
            'selectedCrossSells'    => [
                [
                    'status'        => Transaction::STATUS_APPROVED,
                    'initialAmount' => $this->faker->randomFloat(2)
                ],
                [
                    'status'        => 'declined',
                    'initialAmount' => $this->faker->randomFloat(2)
                ],
                [
                    'status'        => Transaction::STATUS_APPROVED,
                    'initialAmount' => $this->faker->randomFloat(2)
                ]
            ],
            'status'                => Transaction::STATUS_APPROVED,
            'initialAmount'         => $this->faker->randomFloat(2),
            'attemptedTransactions' => [
                'submitAttempt' => $this->faker->numberBetween(1, 10),
                'billerName'    => $this->faker->word
            ],
        ];

        $serviceCollection = new ServiceCollection();
        $serviceCollection->add(
            Service::create('Service name', true)
        );

        $serviceCollection->add(
            Service::create(ServicesList::FRAUD, true)
        );

        $publicKeyCollection = new PublicKeyCollection();

        $publicKeyCollection->add(
            PublicKey::create(
                KeyId::createFromString('3dcc4a19-e2a8-4622-8e03-52247bbd302d'),
                \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', '2019-11-15 16:11:41.0000')
            )
        );

        $this->randomSite = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $serviceCollection,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $publicKeyCollection,
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );
    }

    /**
     * @test
     */
    public function should_call_only_fraud_velocity_when_proper_config_is_true()
    {
        $purchaseProcess = $this->createMock(PurchaseProcess::class);

        $paymentInfo = PaymentInfoFactoryService::create(
            CCPaymentInfo::PAYMENT_TYPE,
            null
        );

        $purchaseProcess->method('paymentInfo')->willReturn($paymentInfo);

        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $purchaseProcessedEvent->method('toArray')->willReturn($this->purchaseProcessedAsArray);
        $processPurchaseCommand = $this->createMock(ProcessPurchaseCommand::class);
        $eventIngestionService  = $this->createMock(EventIngestionService::class);
        $eventIngestionService->expects($this->exactly(2))->method('queue');
        $biLoggerService = $this->createMock(BILoggerService::class);
        $handler = new class($purchaseProcess, $purchaseProcessedEvent, $eventIngestionService, $this->randomSite, $biLoggerService) extends BasePaymentProcessCommandHandler {
            private $purchaseProcessedEvent;

            protected $biLoggerService;

            protected $site;

            public function __construct($purchaseProcess, $purchaseProcessedEvent, $eventIngestionService, $site, $biLoggerService)
            {
                $this->purchaseProcessedEvent = $purchaseProcessedEvent;
                $this->eventIngestionService  = $eventIngestionService;
                $this->biLoggerService        = $biLoggerService;
                $this->purchaseProcess        = $purchaseProcess;
                $this->site                   = $site;
            }

            protected function generatePurchaseBiEvent(): BaseEvent
            {
                return $this->purchaseProcessedEvent;
            }

            public function execute(Command $command)
            {
                return $this->shipBiProcessedPurchaseEvent($this->site);
            }
        };
        Config::set('app.feature.event_ingestion_communication.send_fraud_velocity_event', true);
        Config::set('app.feature.event_ingestion_communication.send_general_bi_events', false);
        $handler->execute($processPurchaseCommand);
    }

    /**
     * @test
     */
    public function should_return_true_when_fraud_service_is_enable_for_site(): void
    {
        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $paymentInfo            = CCPaymentInfo::build('cc', null);
        $this->assertTrue(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseProcessedEvent,
                $this->randomSite,
                $paymentInfo
            )
        );
    }

    /**
     * @test
     */
    public function should_return_true_when_fraud_service_is_enable_for_site_for_cheque_payment_type(): void
    {
        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $paymentInfo            = ChequePaymentInfo::build(ChequePaymentInfo::PAYMENT_TYPE, null);
        $this->assertTrue(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseProcessedEvent,
                $this->randomSite,
                $paymentInfo
            )
        );
    }
    
    /**
     * @test
     */
    public function should_return_false_when_fraud_service_is_enable_for_site_for_cheque_payment_type(): void
    {
        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $paymentInfo            = ChequePaymentInfo::build(ChequePaymentInfo::PAYMENT_TYPE, null);
        $serviceCollection   = new ServiceCollection();
        $publicKeyCollection = new PublicKeyCollection();

        $siteWithouFraudService = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $serviceCollection,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $publicKeyCollection,
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $this->assertFalse(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseProcessedEvent,
                $siteWithouFraudService,
                $paymentInfo
            )
        );
    }

    /**
     * @test
     */
    public function should_return_false_when_fraud_service_is_disable_for_site(): void
    {
        $serviceCollection   = new ServiceCollection();
        $publicKeyCollection = new PublicKeyCollection();

        $siteWithouFraudService = Site::create(
            SiteId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            BusinessGroupId::createFromString('86b95cd0-78ad-4052-9e75-5991c15d6ffa'),
            'http://www.brazzers.com',
            'Brazzers',
            '1111-1111-1111',
            '2222-2222-2222',
            'http://localhost/supportLink',
            'mail@support.com',
            'http://localhost/messageSupportLink',
            'http://localhost/cancellationLink',
            'http://localhost/postbackUrl',
            $serviceCollection,
            'ab3708dc-1415-4654-9403-a4108999a80a',
            $publicKeyCollection,
            'Business group descriptor',
            false,
            false,
            Site::DEFAULT_NUMBER_OF_ATTEMPTS
        );

        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $paymentInfo            = CCPaymentInfo::build('cc', null);
        $this->assertFalse(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseProcessedEvent,
                $siteWithouFraudService,
                $paymentInfo
            )
        );
    }

    /**
     * @test
     */
    public function should_return_false_when_paymentInfo_is_not_CC_and_is_not_Cheque(): void
    {
        $purchaseProcessedEvent = $this->createMock(PurchaseProcessed::class);
        $paymentInfo            = OtherPaymentTypeInfo::build('other', 'paypal');

        $this->assertFalse(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseProcessedEvent,
                $this->randomSite,
                $paymentInfo
            )
        );
    }

    /**
     * @test
     */
    public function should_return_false_when_event_is_not_PurchaseProcessed(): void
    {
        $purchaseInitializedEvent = $this->createMock(PurchaseInitialized::class);
        $paymentInfo              = ChequePaymentInfo::create(
            '999999999',
            '112233',
            false,
            '5233',
            ChequePaymentInfo::PAYMENT_TYPE,
            ChequePaymentInfo::PAYMENT_METHOD
        );

        $this->assertFalse(
            BasePaymentProcessCommandHandler::shouldTriggerFraudVelocityEvent(
                $purchaseInitializedEvent,
                $this->randomSite,
                $paymentInfo
            )
        );
    }

    /**
     * @test
     */
    public function it_should_accept_unionpay_new_range(): void
    {
        //GIVEN
        $pornHubPremiumSiteId       = '299b14d0-cf3d-11e9-8c91-0cc47a283dd2';
        $fakeUnionpayCCWithNewRange = '8164773234719868263';

        $class = new ReflectionClass(BasePaymentProcessCommandHandler::class);

        $method = $class->getMethod('acceptedCard');
        $method->setAccessible(true);

        $anyInstanceOfBasePaymentProcessCommandHandler = new CompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDDTOAssembler::class),
            $this->createMock(TransactionService::class),
            $this->createMock(SessionHandler::class),
            $this->createMock(ConfigService::class),
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(BILoggerService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(CCForBlackListService::class)
        );

        //WHEN
        $result = $method->invokeArgs(
            $anyInstanceOfBasePaymentProcessCommandHandler,
            [
                $fakeUnionpayCCWithNewRange,
                $pornHubPremiumSiteId
            ]
        );

        //THEN
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_should_not_accept_mastercard_for_porn_hub_premium_site_id(): void
    {
        //GIVEN
        $pornHubPremiumSiteId = '299b14d0-cf3d-11e9-8c91-0cc47a283dd2';
        $fakeMasterCardCC     = '5134830749918254';

        $class = new ReflectionClass(BasePaymentProcessCommandHandler::class);

        $method = $class->getMethod('acceptedCard');
        $method->setAccessible(true);

        $anyInstanceOfBasePaymentProcessCommandHandler = new CompleteThreeDCommandHandler(
            $this->createMock(CompleteThreeDDTOAssembler::class),
            $this->createMock(TransactionService::class),
            $this->createMock(SessionHandler::class),
            $this->createMock(ConfigService::class),
            $this->createMock(PurchaseService::class),
            $this->createMock(PostbackService::class),
            $this->createMock(BILoggerService::class),
            $this->createMock(EventIngestionService::class),
            $this->createMock(CCForBlackListService::class)
        );

        //WHEN
        $result = $method->invokeArgs(
            $anyInstanceOfBasePaymentProcessCommandHandler,
            [
                $fakeMasterCardCC,
                $pornHubPremiumSiteId
            ]
        );

        //THEN
        $this->assertFalse($result);
    }
}
