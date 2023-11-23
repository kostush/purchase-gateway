<?php

declare(strict_types=1);

namespace Tests\Integration\PurchaseGateway\Application\Services\SendEmail;

use PHPUnit\Framework\MockObject\MockObject;
use Probiller\Common\EmailSettings;
use ProBillerNG\BI\BILoggerService;
use ProBillerNG\EventIngestion\Domain\EventIngestionService;
use ProBillerNG\Projection\Domain\ItemSourceBuilder;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\Projection\Domain\Projectionist\Projectionist;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\RocketgateBiller;
use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServicesList;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailTemplateService\EmailTemplateService;
use ProBillerNG\PurchaseGateway\Domain\Services\MemberProfileGatewayService;
use ProBillerNG\PurchaseGateway\Domain\Services\PaymentTemplateTranslatingService;
use ProBillerNG\PurchaseGateway\Domain\Services\TransactionService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\NewCCTransactionInformation;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\RocketgateCCRetrieveTransactionResult;
use Tests\IntegrationTestCase;

class SendEmailCommandHandlerTest extends IntegrationTestCase
{
    public const US = "US";

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_send_email_on_main_and_cross_sale_if_purchase_was_successful()
    {
        $purchaseProcessData = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseProcessData
                )
            );

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('transactionId')
            ->willReturn($purchaseProcessData['transaction_collection'][0]['transactionId']);
        $transactionInformation->method('first6')->willReturn((string) $this->faker->randomNumber(6));
        $transactionInformation->method('last4')->willReturn((string) $this->faker->randomNumber(4));
        $transactionInformation->method('amount')->willReturn($this->faker->randomFloat(2, 1));

        $transactionData = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionData->method('currency')->willReturn('USD');
        $transactionData->method('transactionInformation')->willReturn($transactionInformation);
        $transactionData->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionData);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->exactly(2))->method('send');

        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $serviceOptions = $this->createMock(Service::class);
        $serviceOptions->method('options')->willReturn(
            [
                EmailServiceClient::TEMPLATE_ID => 'test'
            ]
        );
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $serviceOptions
            ]
        );

        $biLogger = $this->createMock(BILoggerService::class);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);

        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $emailSettings = new EmailSettings(
            [
                'emailSettingsId' => $this->faker->uuid,
                'emailTemplateId' => $this->faker->uuid,
                'senderId'        => $this->faker->uuid
            ]
        );

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettingsService->method('retrieveEmailSettings')->willReturn($emailSettings);

        $handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $transactionService,
                    $emailService,
                    $this->createMock(ConfigService::class),
                    new EmailTemplateService(),
                    $biLogger,
                    $emailSettingsService,
                    $memberProfileService,
                    $paymentTemplate,
                    $this->createMock(EventIngestionService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($site);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_send_email_when_purchase_was_successful_and_add_flag_with_show_cancel_verbiage_for_us_customer()
    {
        $purchaseProcessData = $this->createPurchaseProcessedWithRocketgateNewPaymentEventDataWithoutCrossSale();

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseProcessData
                )
            );

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('transactionId')
            ->willReturn($purchaseProcessData['transaction_collection'][0]['transactionId']);
        $transactionInformation->method('first6')->willReturn((string) $this->faker->randomNumber(6));
        $transactionInformation->method('last4')->willReturn((string) $this->faker->randomNumber(4));
        $transactionInformation->method('amount')->willReturn($this->faker->randomFloat(2, 1));

        $transactionData = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionData->method('currency')->willReturn('USD');
        $transactionData->method('transactionInformation')->willReturn($transactionInformation);
        $transactionData->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionData);

        // ASSERTION
        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->exactly(1))->method('send');

        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $serviceOptions = $this->createMock(Service::class);
        $serviceOptions->method('options')->willReturn(
            [
                EmailServiceClient::TEMPLATE_ID => 'test'
            ]
        );
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $serviceOptions
            ]
        );

        $biLogger = $this->createMock(BILoggerService::class);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);

        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettingsService->method('retrieveEmailSettings')->willReturn(new EmailSettings(
            [
                'emailSettingsId' => $this->faker->uuid,
                'emailTemplateId' => $this->faker->uuid,
                'senderId'        => $this->faker->uuid
            ]
        ));

        $handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $transactionService,
                    $emailService,
                    $this->createMock(ConfigService::class),
                    new EmailTemplateService(),
                    $biLogger,
                    $emailSettingsService,
                    $memberProfileService,
                    $paymentTemplate,
                    $this->createMock(EventIngestionService::class)
                ]
            )
            ->onlyMethods(['retrieveSite'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($site);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_send_email_on_main_and_cross_sale_if_purchase_was_successful_with_payment_template_null()
    {
        $purchaseProcessData = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseProcessData
                )
            );

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('transactionId')
            ->willReturn($purchaseProcessData['transaction_collection'][0]['transactionId']);
        $transactionInformation->method('first6')->willReturn((string) $this->faker->randomNumber(6));
        $transactionInformation->method('last4')->willReturn((string) $this->faker->randomNumber(4));
        $transactionInformation->method('amount')->willReturn($this->faker->randomFloat(2, 1));

        $transactionData = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionData->method('currency')->willReturn('USD');
        $transactionData->method('transactionInformation')->willReturn($transactionInformation);
        $transactionData->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionData);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->exactly(2))->method('send');

        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $site->method('attempts')->willReturn(Site::DEFAULT_NUMBER_OF_ATTEMPTS);
        $serviceOptions = $this->createMock(Service::class);
        $serviceOptions->method('options')->willReturn(
            [
                EmailServiceClient::TEMPLATE_ID => 'test'
            ]
        );
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $serviceOptions
            ]
        );

        $biLogger = $this->createMock(BILoggerService::class);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);

        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettingsService->method('retrieveEmailSettings')->willReturn(new EmailSettings());

        $configService = $this->createMock(ConfigService::class);

        $handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $transactionService,
                    $emailService,
                    $configService,
                    new EmailTemplateService(),
                    $biLogger,
                    $emailSettingsService,
                    $memberProfileService,
                    $paymentTemplate,
                    $this->createMock(EventIngestionService::class)
                ]
            )
            ->onlyMethods(['retrievePaymentTemplateData', 'retrieveSite'])
            ->getMock();

        $handler->method('retrievePaymentTemplateData')->willReturn(null);
        $handler->method('retrieveSite')->willReturn($site);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_send_email_on_main_and_cross_sale_if_purchase_was_successful_with_payment_template()
    {
        $purchaseProcessData = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseProcessData
                )
            );

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('transactionId')
            ->willReturn($purchaseProcessData['transaction_collection'][0]['transactionId']);
        $transactionInformation->method('first6')->willReturn((string) $this->faker->randomNumber(6));
        $transactionInformation->method('last4')->willReturn((string) $this->faker->randomNumber(4));
        $transactionInformation->method('amount')->willReturn($this->faker->randomFloat(2, 1));

        $transactionData = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionData->method('currency')->willReturn('USD');
        $transactionData->method('transactionInformation')->willReturn($transactionInformation);
        $transactionData->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionData);

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->exactly(2))->method('send');

        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $serviceOptions = $this->createMock(Service::class);
        $serviceOptions->method('options')->willReturn(
            [
                EmailServiceClient::TEMPLATE_ID => 'test'
            ]
        );
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $serviceOptions
            ]
        );

        $biLogger = $this->createMock(BILoggerService::class);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);

        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);
        $paymentTemplateModel = $this->createMock(PaymentTemplate::class);

        $paymentTemplate->method('retrievePaymentTemplate')->willReturn($paymentTemplateModel);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);
        $emailSettingsService->method('retrieveEmailSettings')->willReturn(new EmailSettings());

        $handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $transactionService,
                    $emailService,
                    $this->createMock(ConfigService::class),
                    new EmailTemplateService(),
                    $biLogger,
                    $emailSettingsService,
                    $memberProfileService,
                    $paymentTemplate,
                    $this->createMock(EventIngestionService::class)
                ]
            )
            ->onlyMethods(['retrievePaymentTemplateData', 'retrieveSite'])
            ->getMock();

        $handler->method('retrievePaymentTemplateData')->willReturn($paymentTemplateModel);
        $handler->method('retrieveSite')->willReturn($site);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $eventMock);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_send_email_on_main_and_cross_sale_with_correct_card_number_if_purchase_was_successful()
    {
        // Set full payload with cross sale
        $purchaseProcessData = $this->createPurchaseProcessedWithRocketgateNewPaymentEventData();

        /** @var ItemToWorkOn|MockObject $handler */
        $eventMock = $this->createMock(ItemToWorkOn::class);
        $eventMock->method('body')
            ->willReturn(
                json_encode(
                    $purchaseProcessData
                )
            );

        $transactionInformation = $this->createMock(NewCCTransactionInformation::class);
        $transactionInformation->method('transactionId')
            ->willReturn($purchaseProcessData['transaction_collection'][0]['transactionId']);
        $transactionInformation->method('first6')->will($this->onConsecutiveCalls((string) $this->faker->randomNumber(6), null));
        $transactionInformation->method('last4')->will($this->onConsecutiveCalls((string) $this->faker->randomNumber(4), null));
        $transactionInformation->method('amount')->willReturn($this->faker->randomFloat(2, 1));

        $transactionData = $this->createMock(RocketgateCCRetrieveTransactionResult::class);
        $transactionData->method('currency')->willReturn('USD');
        $transactionData->method('transactionInformation')->willReturn($transactionInformation);
        $transactionData->method('billerName')->willReturn(RocketgateBiller::BILLER_NAME);

        $transactionService = $this->createMock(TransactionService::class);
        $transactionService->method('getTransactionDataBy')->willReturn($transactionData);

        $site = $this->createMock(Site::class);
        $site->method('name')->willReturn('test');
        $site->method('descriptor')->willReturn('test');
        $site->method('supportLink')->willReturn('test');
        $site->method('phoneNumber')->willReturn('test');
        $site->method('mailSupportLink')->willReturn('test');
        $site->method('messageSupportLink')->willReturn('test');
        $site->method('skypeNumber')->willReturn('test');
        $site->method('url')->willReturn('test');
        $site->method('publicKeys')->willReturn([$this->faker->uuid]);
        $serviceOptions = $this->createMock(Service::class);
        $serviceOptions->method('options')->willReturn(
            [
                EmailServiceClient::TEMPLATE_ID => 'test'
            ]
        );
        $site->method('services')->willReturn(
            [
                ServicesList::EMAIL_SERVICE => $serviceOptions
            ]
        );

        $biLogger = $this->createMock(BILoggerService::class);

        $memberProfileService = $this->createMock(MemberProfileGatewayService::class);

        $paymentTemplate = $this->createMock(PaymentTemplateTranslatingService::class);

        $emailSettingsService = $this->createMock(EmailSettingsService::class);

        $emailSettingsService->method('retrieveEmailSettings')->willReturn(new EmailSettings(
            [
            'emailSettingsId' => $this->faker->uuid,
            'emailTemplateId' => $this->faker->uuid,
            'senderId'        => $this->faker->uuid
            ]
        ));

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->exactly(2))->method('send');

        $emailTemplateService = $this->createMock(EmailTemplateService::class);
        $emailTemplateService->expects($this->any())->method('getTemplate');

        $handler = $this->getMockBuilder(SendEmailsCommandHandler::class)
            ->setConstructorArgs(
                [
                    $this->createMock(Projectionist::class),
                    new ItemSourceBuilder(),
                    $transactionService,
                    $emailService,
                    $this->createMock(ConfigService::class),
                    $emailTemplateService,
                    $biLogger,
                    $emailSettingsService,
                    $memberProfileService,
                    $paymentTemplate,
                    $this->createMock(EventIngestionService::class)
                ]
            )
            ->onlyMethods(['retrieveSite','emailSettingsService'])
            ->getMock();

        $handler->method('retrieveSite')->willReturn($site);
        $handler->method('emailSettingsService')->willReturn($emailSettingsService);

        $reflection = new \ReflectionClass(SendEmailsCommandHandler::class);
        $method     = $reflection->getMethod('operation');
        $method->setAccessible(true);

        $method->invoke($handler, $eventMock);
    }
}
