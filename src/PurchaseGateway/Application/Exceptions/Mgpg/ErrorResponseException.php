<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions\Mgpg;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class ErrorResponseException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::MGPG_ERROR_RESPONSE;
}
