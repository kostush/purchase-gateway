<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class BlockedDueToFraudException
 * @package ProBillerNG\PurchaseGateway\Application\Exceptions
 */
class BlockedDueToFraudException extends Exception
{
    protected $code = Code::BLOCKED_DUE_TO_FRAUD_EXCEPTION;
}
