<?php

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class SessionNotFoundException extends Exception
{
    protected $code = Code::SESSION_NOT_FOUND;

    /**
     * SessionNotFoundException constructor.
     * @param \Throwable|null $previous  Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct($previous);
    }
}
