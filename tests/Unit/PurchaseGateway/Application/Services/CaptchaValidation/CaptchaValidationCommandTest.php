<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\CaptchaValidation;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidStepForCaptchaValidationException;
use ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation\CaptchaValidationCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use Tests\UnitTestCase;

class CaptchaValidationCommandTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws InvalidStepForCaptchaValidationException
     * @throws LoggerException
     */
    public function it_should_throw_an_invalid_step_for_captcha_validation_exception_if_step_invalid()
    {
        $this->expectException(InvalidStepForCaptchaValidationException::class);

        new CaptchaValidationCommand(
            'invalid',
            'testToken',
            'testSessionId',
            'testSiteId'
        );
    }

    /**
     * @test
     * @depends it_should_throw_an_invalid_step_for_captcha_validation_exception_if_step_invalid
     * @return CaptchaValidationCommand
     * @throws InvalidStepForCaptchaValidationException
     * @throws LoggerException
     */
    public function it_should_return_a_captcha_validation_command_when_correct_data_is_sent()
    {
        $captchaValidationCommand = new CaptchaValidationCommand(
            FraudAdvice::FOR_PROCESS,
            'testToken',
            'testSessionId',
            'testSiteId'
        );

        $this->assertInstanceOf(CaptchaValidationCommand::class, $captchaValidationCommand);

        return $captchaValidationCommand;
    }

    /**
     * @test
     * @param CaptchaValidationCommand $captchaValidationCommand Captcha validation command
     * @depends it_should_return_a_captcha_validation_command_when_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_step(CaptchaValidationCommand $captchaValidationCommand)
    {
        $this->assertNotEmpty($captchaValidationCommand->step());
    }

    /**
     * @test
     * @param CaptchaValidationCommand $captchaValidationCommand Captcha validation command
     * @depends it_should_return_a_captcha_validation_command_when_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_site_id(CaptchaValidationCommand $captchaValidationCommand)
    {
        $this->assertNotEmpty($captchaValidationCommand->siteId());
    }

    /**
     * @test
     * @param CaptchaValidationCommand $captchaValidationCommand Captcha validation command
     * @depends it_should_return_a_captcha_validation_command_when_correct_data_is_sent
     * @return void
     */
    public function it_should_contain_a_token(CaptchaValidationCommand $captchaValidationCommand)
    {
        $this->assertNotEmpty($captchaValidationCommand->token());
    }
}
