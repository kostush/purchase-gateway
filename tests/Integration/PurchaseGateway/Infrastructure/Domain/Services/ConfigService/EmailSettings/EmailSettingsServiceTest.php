<?php

namespace PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings;

use Probiller\Common\EmailSettings;
use Probiller\Common\Enums\BillerType\BillerType;
use Probiller\Common\Enums\BusinessTransactionOperation\BusinessTransactionOperation;
use Probiller\Common\Enums\MemberType\MemberType;
use Probiller\Service\Config\ProbillerConfigClient;
use ProBillerNG\PurchaseGateway\Application\Services\SendEmails\SendEmailsCommandHandler;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\EmailSettings\EmailSettingsService;
use Tests\IntegrationTestCase;

use UnexpectedValueException;
use const Grpc\STATUS_OK;
use const Grpc\STATUS_NOT_FOUND;
use const Grpc\STATUS_UNKNOWN;

class EmailSettingsServiceTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_valid_email_setting_when_email_settings_exist(): void
    {
        $emailSetting = new EmailSettings(['emailSettingsId' => $this->faker->uuid]);

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new \stdClass();
        $status->code = STATUS_OK;

        $unaryCallMock->method('wait')->willReturn(
            [
                $emailSetting,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetEmailSettingsConfig')->willReturn(
            $unaryCallMock
        );

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $result = $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(EmailSettings::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_null_when_email_settings_dont_exist(): void
    {
        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new \stdClass();
        $status->code = STATUS_NOT_FOUND;

        $unaryCallMock->method('wait')->willReturn(
            [
                null,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetEmailSettingsConfig')->willReturn(
            $unaryCallMock
        );

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $result = $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertNull($result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_when_got_a_code_error_code(): void
    {
        $status          = new \stdClass();
        $status->code    = STATUS_UNKNOWN;
        $status->details = "Error message";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Error message");
        $this->expectExceptionCode(STATUS_UNKNOWN);

        $emailSetting = new EmailSettings(['emailSettingsId' => $this->faker->uuid]);

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock->method('wait')->willReturn(
            [
                $emailSetting,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetEmailSettingsConfig')->willReturn(
            $unaryCallMock
        );

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_when_pass_a_invalid_member_type(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("MemberType 0 is invalid");

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $invalidMemberTypeParam = MemberType::PBnew;

        $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            'rocketgate',
            $invalidMemberTypeParam,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_when_pass_a_invalid_biller_name(): void
    {
        $invalidBillerNameParam = $this->faker->colorName;

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Enum %s has no value defined for name %s',
                BillerType::class,
                $invalidBillerNameParam
            )
        );

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            $invalidBillerNameParam,
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_throw_exception_when_pass_an_invalid_tax_type(): void
    {
        $emailSetting = new EmailSettings(['emailSettingsId' => $this->faker->uuid]);

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new \stdClass();
        $status->code = STATUS_OK;

        $unaryCallMock->method('wait')->willReturn(
            [
                $emailSetting,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetEmailSettingsConfig')->willReturn(
            $unaryCallMock
        );

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);


        $invalidTaxType = $this->faker->colorName;

        $result =  $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            $invalidTaxType,
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(EmailSettings::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_null_when_email_settings_exist_but_is_disabled(): void
    {
        $emailSetting = new EmailSettings(['emailSettingsId' => $this->faker->uuid, 'disabled' => true]);

        $probillerConfigClientMock = $this->getMockBuilder(ProbillerConfigClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $unaryCallMock = $this->getMockBuilder(\Grpc\UnaryCall::class)
            ->disableOriginalConstructor()
            ->getMock();

        $status       = new \stdClass();
        $status->code = STATUS_OK;

        $unaryCallMock->method('wait')->willReturn(
            [
                $emailSetting,
                $status
            ]
        );

        $probillerConfigClientMock->method('GetEmailSettingsConfig')->willReturn(
            $unaryCallMock
        );

        $configService       = new ConfigService($probillerConfigClientMock);
        $emailSettingService = new EmailSettingsService($configService);

        $result = $emailSettingService->retrieveEmailSettings(
            $this->faker->uuid,
            'vat',
            'rocketgate',
            SendEmailsCommandHandler::MEMBER_TYPE_NEW,
            BusinessTransactionOperation::SINGLECHARGEPURCHASE,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertNull($result);
    }
}