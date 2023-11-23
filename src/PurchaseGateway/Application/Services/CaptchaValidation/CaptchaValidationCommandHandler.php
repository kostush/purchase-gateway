<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Services\BaseCommandHandler;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidCommandException;
use ProBillerNG\PurchaseGateway\Application\Services\PurchaseProcessHandler;
use ProBillerNG\PurchaseGateway\Application\Services\TokenDecoder;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;

class CaptchaValidationCommandHandler extends BaseCommandHandler
{
    /**
     * @var TokenDecoder
     */
    private $tokenDecoder;

    /**
     * @var PurchaseProcessHandler
     */
    private $userSessionInfoHandler;

    /**
     * CaptchaValidationCommandHandler constructor.
     * @param TokenDecoder           $tokenDecoder           Token decoder
     * @param PurchaseProcessHandler $userSessionInfoHandler Session handler
     */
    public function __construct(TokenDecoder $tokenDecoder, PurchaseProcessHandler $userSessionInfoHandler)
    {
        $this->tokenDecoder           = $tokenDecoder;
        $this->userSessionInfoHandler = $userSessionInfoHandler;
    }

    /**
     * @param Command $command Command
     * @return void
     * @throws InvalidCommandException
     * @throws LoggerException
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     */
    public function execute(Command $command): void
    {
        if (!$command instanceof CaptchaValidationCommand) {
            throw new InvalidCommandException(CaptchaValidationCommand::class, $command);
        }
        $purchaseProcess = $this->userSessionInfoHandler->load($command->sessionId());

        Log::info('Mark captcha validated', ['step' => $command->step()]);

        try {
            $this->validateCaptchaByStep($purchaseProcess, $command->step());
        } finally {
            // Store user session
            $this->userSessionInfoHandler->update($purchaseProcess);
        }
    }

    /**
     * @param PurchaseProcess $purchaseProcess PurchaseProcess
     * @param string          $step            Step
     * @return void
     * @throws LoggerException
     * @throws \ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\IllegalStateTransitionException
     */
    private function validateCaptchaByStep(PurchaseProcess $purchaseProcess, string $step): void
    {
        switch ($step) {
            case FraudAdvice::FOR_INIT:
                $purchaseProcess->validateInitCaptcha();
                break;
            case FraudAdvice::FOR_PROCESS:
                $purchaseProcess->validateProcessCaptcha();
                break;
        }
    }
}
