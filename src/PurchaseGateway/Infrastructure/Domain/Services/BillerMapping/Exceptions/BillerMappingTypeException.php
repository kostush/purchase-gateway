<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BillerMapping\BillerMappingException;

class BillerMappingTypeException extends BillerMappingException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::BILLER_MAPPING_TYPE_EXCEPTION;
}
