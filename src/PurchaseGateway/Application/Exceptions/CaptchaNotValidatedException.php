<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

/**
 * Class CaptchaNotValidatedException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CaptchaNotValidatedException extends ValidationException
{
    protected $code = Code::CAPTCHA_NOT_VALIDATED_EXCEPTION;
}
