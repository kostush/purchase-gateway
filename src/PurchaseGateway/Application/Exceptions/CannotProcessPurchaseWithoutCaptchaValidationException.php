<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

/**
 * Class CannotProcessPurchaseWithoutCaptchaValidationException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CannotProcessPurchaseWithoutCaptchaValidationException extends ValidationException
{
    protected $code = Code::CANNOT_PROCESS_PURCHASE_WITHOUT_CAPTCHA_VALIDATION;
}
