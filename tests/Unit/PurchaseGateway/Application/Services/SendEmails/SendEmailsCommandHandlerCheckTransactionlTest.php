<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\SendEmails;

use PHPUnit\Framework\MockObject\MockObject;
use Probiller\Common\EmailSettings;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailService;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\EmailTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\Templates\EmailTemplateGenericProbiller;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\CheckTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RetrieveTransactionResult;
use Tests\UnitTestCase;

class SendEmailsCommandHandlerCheckTransactionTest extends UnitTestCase
{
    /**
     * @var MockObject|SendEmailsCommandHandler
     */
    private $handler;

    /** @var RetrieveTransactionResult */
    private $retriveTransactionMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'handleMainPurchase',
                    'handleCrossSalePurchases',
                    'sendEmail',
                    'retrieveSite',
                    'retrieveTransactionData',
                    'emailTemplateService',
                    'emailService',
                    'requestSession',
                    'biLoggerService',
                    'emailSettingsService',
                    'MemberProfileGatewayService',
                    'retrievePaymentTemplateData'
                ]
            )
            ->getMock();

        $mockRocketGateSettings = $this->createMock(CheckTransactionInformation::class);
        $mockRocketGateSettings->method('billerName')->willReturn('rocketgate');

        $transactionDataMock = $this->createMock(RetrieveTransactionResult::class);
        $transactionDataMock->method('transactionInformation')->willReturn($mockRocketGateSettings);

        $this->retriveTransactionMock = $transactionDataMock;
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_not_process_event_if_last_transaction_not_approved()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
                        [
                            'transactionCollection' => [
                                [
                                    'state'         => Transaction::STATUS_DECLINED,
                                    'transactionId' => $this->faker->uuid
                                ]
                            ]
                        ]
                    )
                )
            );

        $this->handler->expects($this->never())->method('handleMainPurchase');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_handle_main_purchase_from_event()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $this->createPurchaseProcessedWithRocketgateNewPaymentEventData()
                )
            );

        $this->handler->expects($this->once())->method('handleMainPurchase');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_not_handle_cross_sales_from_event_if_they_do_not_exist()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
                        [
                            'transactionCollectionCrossSale' => [
                                [
                                    'state'         => Transaction::STATUS_DECLINED,
                                    'transactionId' => $this->faker->uuid
                                ]
                            ]
                        ]
                    )
                )
            );

        $this->handler->expects($this->once())->method('handleCrossSalePurchases');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function operation_should_handle_cross_sales_from_event()
    {
        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $this->createPurchaseProcessedWithRocketgateNewPaymentEventData()
                )
            );

        $this->handler->expects($this->once())->method('handleCrossSalePurchases');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($this->handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function handle_main_purchase_should_send_email()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $this->handler->expects($this->once())->method('sendEmail');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('handleMainPurchase');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function handle_cross_sales_should_send_email_for_each_cross_sale()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $this->handler->expects($this->once())->method('sendEmail');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('handleCrossSalePurchases');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function handle_cross_sales_should_not_send_email_if_transaction_was_not_approved()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(
            json_encode(
                $this->createPurchaseProcessedWithRocketgateNewPaymentEventData(
                    [
                        'transactionCollectionCrossSale' => [
                            [
                                'state'         => Transaction::STATUS_DECLINED,
                                'transactionId' => $this->faker->uuid
                            ]
                        ]
                    ]
                )
            )
        );

        $this->handler->expects($this->never())->method('sendEmail');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('handleCrossSalePurchases');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_stop_process_we_do_not_have_options_set_for_email_service_on_site_configuration()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $this->handler->expects($this->never())->method('retrievePaymentTemplateData');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_stop_process_if_email_settings_service_returns_null_response()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $this->createMock(Service::class)
            ]
        );
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettingsService->method('retrieveEmailSettings')->willReturn(null);

        $this->handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $this->handler->expects($this->never())->method('retrievePaymentTemplateData');

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_retrieve_template()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $this->createMock(Service::class)
            ]
        );
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $templateService = $this->createMock(EmailTemplateService::class);
        $templateService->expects($this->once())->method('getTemplate');

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);
        $this->handler->method('memberProfileGatewayService')->willReturn($memberProfileService);

        $this->handler->method('emailTemplateService')->willReturn($templateService);
        $this->handler->method('retrievePaymentTemplateData')->willReturn(null);

        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettings = new EmailSettings();
        $emailSettingsService->method('retrieveEmailSettings')->willReturn($emailSettings);

        $this->handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_call_email_service_to_send_email()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $this->createMock(Service::class)
            ]
        );
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $emailTemplate = $this->getMockBuilder(EmailTemplateGenericProbiller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'templateId',
                    'templateData',
                ]
            )
            ->getMock();
        $emailTemplate->method('templateId')->willReturn('probiller-template');
        $emailTemplate->method('templateData')->willReturn([]);
        $templateService = $this->createMock(EmailTemplateService::class);
        $templateService->method('getTemplate')->willReturn($emailTemplate);

        $this->handler->method('emailTemplateService')->willReturn($templateService);

        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);
        $this->handler->method('memberProfileGatewayService')->willReturn($memberProfileService);

        $biLogger = $this->createMock(BILoggerService::class);
        $this->handler->method('biLoggerService')->willReturn($biLogger);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())->method('send');

        $this->handler->method('emailService')->willReturn($emailService);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettings = new EmailSettings();
        $emailSettingsService->method('retrieveEmailSettings')->willReturn($emailSettings);

        $this->handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_write_bi_event()
    {
        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $this->createMock(Service::class)
            ]
        );
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $emailTemplate = $this->getMockBuilder(EmailTemplateGenericProbiller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'templateId',
                    'templateData',
                ]
            )
            ->getMock();
        $emailTemplate->method('templateId')->willReturn($this->faker->uuid);
        $emailTemplate->method('templateData')->willReturn([]);
        $templateService = $this->createMock(EmailTemplateService::class);
        $templateService->method('getTemplate')->willReturn($emailTemplate);

        $this->handler->method('emailTemplateService')->willReturn($templateService);

        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $biLogger = $this->createMock(BILoggerService::class);
        $biLogger->expects($this->once())->method('write');
        $this->handler->method('biLoggerService')->willReturn($biLogger);

        $emailService = $this->createMock(EmailService::class);
        $this->handler->method('emailService')->willReturn($emailService);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettings = new EmailSettings();
        $emailSettingsService->method('retrieveEmailSettings')->willReturn($emailSettings);

        $this->handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function send_email_should_regenerate_session_id()
    {
        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $property   = $reflection->getProperty('requestSessionId');
        $property->setAccessible(true);
        $initialSessionId = SessionId::create();
        $property->setValue($this->handler, $initialSessionId);

        $purchaseProcessed = PurchaseProcessed::createFromJson(json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData()));

        $site = $this->createMock(Site::class);
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $this->createMock(Service::class)
            ]
        );
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $this->handler->method('retrieveSite')->willReturn(
            $site
        );

        $emailTemplate = $this->getMockBuilder(EmailTemplateGenericProbiller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'templateId',
                    'templateData',
                ]
            )
            ->getMock();
        $emailTemplate->method('templateId')->willReturn($this->faker->uuid);
        $emailTemplate->method('templateData')->willReturn([]);
        $templateService = $this->createMock(EmailTemplateService::class);
        $templateService->method('getTemplate')->willReturn($emailTemplate);

        $this->handler->method('emailTemplateService')->willReturn($templateService);

        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $biLogger = $this->createMock(BILoggerService::class);
        $this->handler->method('biLoggerService')->willReturn($biLogger);

        $emailService = $this->createMock(EmailService::class);
        $this->handler->method('emailService')->willReturn($emailService);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettings = new EmailSettings();
        $emailSettingsService->method('retrieveEmailSettings')->willReturn($emailSettings);

        $this->handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $method = $reflection->getMethod('sendEmail');
        $method->setAccessible(true);

        $method->invoke($this->handler, $purchaseProcessed, $this->faker->uuid, $this->faker->uuid, 'taxType');

        $finalSessionId = $property->getValue($this->handler);

        $this->assertNotEquals($initialSessionId, $finalSessionId);
    }

    /**
     *  @test
     *  @return void
     */
    public function send_email_should_not_contain_transactiondata_cc_details()
    {
        $this->retriveTransactionMock->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);
        $this->handler->method('retrieveTransactionData')->willReturn($this->retriveTransactionMock);

        $this->assertEquals(false, method_exists($this->retriveTransactionMock->transactionInformation(), 'first6'));
        $this->assertEquals(false, method_exists($this->retriveTransactionMock->transactionInformation(), 'last4'));
        $this->assertEquals(false, method_exists($this->retriveTransactionMock->transactionInformation(), 'cardExpirationYear'));
        $this->assertEquals(false, method_exists($this->retriveTransactionMock->transactionInformation(), 'cardExpirationMonth'));
    }
}
