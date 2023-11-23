<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class ReturnException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::MGPG_RETURN_VALIDATION_EXCEPTION;
}
