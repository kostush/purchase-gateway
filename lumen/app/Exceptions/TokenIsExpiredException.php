<?php

declare(strict_types=1);

namespace App\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use Throwable;

class TokenIsExpiredException extends ApplicationException
{
    public $code = Code::TOKEN_EXPIRED;

    /**
     * TokenIsExpiredException constructor.
     * @param Throwable|null $previous previous
     */
    public function __construct(Throwable $previous = null)
    {
        parent::__construct(Code::getMessage($this->code), $this->code, $previous);
    }
}
