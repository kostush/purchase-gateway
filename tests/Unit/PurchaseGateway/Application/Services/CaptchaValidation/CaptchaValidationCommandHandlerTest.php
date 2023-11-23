<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Application\Services\CaptchaValidation;

use ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation\CaptchaValidationCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use Tests\UnitTestCase;

class CaptchaValidationCommandHandlerTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_call_validate_init_captcha_on_purchase_process(): void
    {
        $captchaValidationCommandHandler = $this->createMock(CaptchaValidationCommandHandler::class);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->expects($this->once())->method('validateInitCaptcha');

        $handler               = new \ReflectionClass($captchaValidationCommandHandler);
        $validateCaptchaByStep = $handler->getMethod('validateCaptchaByStep');
        $validateCaptchaByStep->setAccessible(true);
        $validateCaptchaByStep->invokeArgs($captchaValidationCommandHandler, [$purchaseProcess, FraudAdvice::FOR_INIT]);
    }

    /**
     * @test
     * @return void
     * @throws \ReflectionException
     */
    public function it_should_call_validate_process_captcha_on_purchase_process(): void
    {
        $captchaValidationCommandHandler = $this->createMock(CaptchaValidationCommandHandler::class);

        $purchaseProcess = $this->createMock(PurchaseProcess::class);
        $purchaseProcess->expects($this->once())->method('validateProcessCaptcha');

        $handler               = new \ReflectionClass($captchaValidationCommandHandler);
        $validateCaptchaByStep = $handler->getMethod('validateCaptchaByStep');
        $validateCaptchaByStep->setAccessible(true);
        $validateCaptchaByStep->invokeArgs(
            $captchaValidationCommandHandler,
            [$purchaseProcess, FraudAdvice::FOR_PROCESS]
        );
    }
}
