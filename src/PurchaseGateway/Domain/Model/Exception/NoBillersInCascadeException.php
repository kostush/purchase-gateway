<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

/**
 * Class NoBillersInCascadeException
 * @package ProBillerNG\PurchaseGateway\Application\Exceptions
 */
class NoBillersInCascadeException extends ValidationException
{
    protected $code = Code::NO_BILLERS_IN_CASCADE_EXCEPTION;
}
