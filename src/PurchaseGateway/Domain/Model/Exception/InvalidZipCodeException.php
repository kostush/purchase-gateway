<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidZipCodeException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_ZIP_CODE;
}
