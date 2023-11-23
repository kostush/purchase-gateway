<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;
use ProBillerNG\PurchaseGateway\Exception;

class BillerMappingException extends Exception implements IncreasePurchaseAttempts, Returns400Code
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::BILLER_MAPPING_EXCEPTION;
}
