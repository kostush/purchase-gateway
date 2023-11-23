<?php

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidNextBillerException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_NEXT_BILLER;
}
