<?php

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class UnknownBillerDtoException extends Exception
{
    protected $code = Code::UNKNOWN_BILLER_DTO_EXCEPTION;

    /**
     * UnknownBillerDtoException constructor.
     * @param string          $dtoClass Dto class
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $dtoClass, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $dtoClass);
    }
}
