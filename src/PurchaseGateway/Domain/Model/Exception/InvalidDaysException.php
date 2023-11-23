<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidDaysException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_DAYS;
}
