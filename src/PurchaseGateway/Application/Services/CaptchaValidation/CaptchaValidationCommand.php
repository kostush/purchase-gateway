<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CaptchaValidation;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Application\Exceptions\InvalidStepForCaptchaValidationException;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\FraudAdvice;

class CaptchaValidationCommand extends Command
{
    private $steps = [
        FraudAdvice::FOR_INIT,
        FraudAdvice::FOR_PROCESS
    ];

    /**
     * @var string
     */
    private $step;

    /**
     * @var string
     */
    private $token;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $siteId;

    /**
     * CaptchaValidationCommand constructor.
     * @param string $step      Step to validate captca on
     * @param string $token     Token
     * @param string $sessionId The decoded token
     * @param string $siteId    The site id
     * @throws InvalidStepForCaptchaValidationException
     * @throws LoggerException
     */
    public function __construct(string $step, string $token, string $sessionId, string $siteId)
    {
        $this->initStep($step);
        $this->token     = $token;
        $this->sessionId = $sessionId;
        $this->siteId    = $siteId;
    }

    /**
     * @param string $step Step to validate captcha on
     * @return void
     * @throws InvalidStepForCaptchaValidationException
     * @throws LoggerException
     */
    private function initStep(string $step): void
    {
        if (!in_array($step, $this->steps)) {
            throw new InvalidStepForCaptchaValidationException($step);
        }

        $this->step = $step;
    }

    /**
     * @return string
     */
    public function step(): string
    {
        return $this->step;
    }

    /**
     * @return string
     */
    public function token(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function siteId(): string
    {
        return $this->siteId;
    }
}
