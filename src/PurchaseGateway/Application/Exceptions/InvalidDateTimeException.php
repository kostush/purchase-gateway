<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Exception;
use ProBillerNG\PurchaseGateway\Code;

class InvalidDateTimeException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_DATETIME_EXCEPTION;
}
