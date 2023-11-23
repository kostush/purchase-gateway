<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;
use ProBillerNG\Logger\Exception as LoggerException;

/**
 * Class InvalidStepForCaptchaValidationException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class InvalidStepForCaptchaValidationException extends ValidationException
{
    protected $code = Code::INVALID_STEP_FOR_CAPTCHA_VALIDATION_EXCEPTION;

    /**
     * InvalidStepForCaptchaValidationException constructor.
     *
     * @param string          $step     Step to validate captcha on
     * @param \Throwable|null $previous Previous exception
     * @throws LoggerException
     */
    public function __construct(string $step, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $step);
    }
}
