<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class ForceCascadeException extends Exception
{
    protected $code = Code::FORCE_CASCADE_EXCEPTION;
}
