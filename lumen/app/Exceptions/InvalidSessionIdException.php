<?php

namespace App\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use Throwable;

class InvalidSessionIdException extends ApplicationException
{
    public $code = Code::APPLICATION_EXCEPTION_INVALID_SESSION_ID;

    /**
     * InvalidSessionIdException constructor.
     * @param Throwable|null $previous previous
     */
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(Code::getMessage($this->code), $this->code, $previous);
    }
}
