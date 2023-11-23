<?php

namespace SendEmail;

use Probiller\Common\EmailSettings;
use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use Tests\System\ProcessPurchase\ProcessPurchaseBase;
use Tests\SystemTestCase;

class SendEmailCommandHandlerTest extends SystemTestCase
{
    /**
     * @var SendEmailsCommandHandler $sendEmailCommandHandler
     */
    protected $sendEmailCommandHandler;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->sendEmailCommandHandler = app(SendEmailsCommandHandler::class);
    }

    /**
     * @test
     *
     * @throws Exception
     * @throws EmailSettingsException
     */
    public function handler_should_be_able_to_request_email_settings()
    {
        $validSiteId          = ProcessPurchaseBase::TESTING_SITE;
        $emailSettingsService = $this->sendEmailCommandHandler->emailSettingsService();
        $emailSettings        = $emailSettingsService->retrieveEmailSettings(
            $validSiteId,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(EmailSettingsService::class, $emailSettingsService);
        $this->assertInstanceOf(EmailSettings::class, $emailSettings);
        $this->assertNotNull($emailSettings->getEmailTemplateId());
        $this->assertNotNull($emailSettings->getSenderId());
    }
}
