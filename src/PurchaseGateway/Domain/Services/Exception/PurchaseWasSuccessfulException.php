<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

class PurchaseWasSuccessfulException extends ValidationException
{
    protected $code = Code::PURCHASE_WAS_SUCCESSFUL;
}