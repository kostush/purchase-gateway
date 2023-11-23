<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class TokenIsExpiredException extends Exception
{
    protected $code = Code::TOKEN_EXPIRED;
}
