<?php

namespace ConfigService;

use Probiller\Common\EmailSettings;
use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use Tests\SystemTestCase;

class RetrieveEmailSettingsConfigServiceTest extends SystemTestCase
{
    /**
     * @var EmailSettingsService $emailSettingsService
     */
    protected $emailSettingsService;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->emailSettingsService = app(EmailSettingsService::class);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function should_return_a_valid_email_settings_when_passed_a_valid_params_group()
    {
        $validSiteId   = '8e34c94e-135f-4acb-9141-58b3a6e56c74';
        $emailSettings = $this->emailSettingsService->retrieveEmailSettings(
            $validSiteId,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SUBSCRIPTIONPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(EmailSettings::class, $emailSettings);
        $this->assertNotNull($emailSettings->getEmailTemplateId());
        $this->assertNotNull($emailSettings->getSenderId());
    }

    /**
     * @test
     * @throws \Exception
     */
    public function should_return_null_when_passed_a_not_existent_params_params_group()
    {
        $invalidSiteId = $this->faker->uuid;

        $this->assertNull(
            $this->emailSettingsService->retrieveEmailSettings(
                $invalidSiteId,
                'vat',
                'rocketgate',
                SendEmailsCommandHandler::MEMBER_TYPE_NEW,
                BusinessTransactionOperation::SINGLECHARGEPURCHASE,
                $this->faker->uuid,
                $this->faker->uuid
            )
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function should_throw_exception_when_passed_an_unexpected_value()
    {
        $this->expectException(\UnexpectedValueException::class);

        $unexpectedBillerName = $this->faker->country;
        $this->assertFalse(
            $this->emailSettingsService->retrieveEmailSettings(
                $this->faker->uuid,
                'vat',
                $unexpectedBillerName,
                SendEmailsCommandHandler::MEMBER_TYPE_NEW,
                BusinessTransactionOperation::SINGLECHARGEPURCHASE,
                $this->faker->uuid,
                $this->faker->uuid
            )
        );
    }
}
