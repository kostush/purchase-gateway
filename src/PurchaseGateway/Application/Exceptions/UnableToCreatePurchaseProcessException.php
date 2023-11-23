<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class UnableToCreatePurchaseProcessException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::UNABLE_TO_CREATE_PURCHASE_PROCESS;
}
