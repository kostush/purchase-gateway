<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidBillerFieldsDataException extends Exception
{
    protected $code = Code::INVALID_BILLER_FIELDS_DATA;
}