<?php

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidTokenSessionException extends Exception
{
    public $code = Code::APPLICATION_EXCEPTION_INVALID_SESSION_ID;
}
