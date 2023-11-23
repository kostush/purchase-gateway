<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

/**
 * Class CannotValidateProcessCaptchaWithoutInitCaptchaException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CannotValidateProcessCaptchaWithoutInitCaptchaException extends ValidationException
{
    protected $code = Code::CANNOT_VALIDATE_CAPTCHA_PROCESS_EXCEPTION;
}
