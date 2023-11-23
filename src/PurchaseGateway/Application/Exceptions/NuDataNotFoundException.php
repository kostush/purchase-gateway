<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class NuDataNotFoundException extends Exception
{
    protected $code = Code::NU_DATA_NOT_FOUND_EXCEPTION;

    /**
     * NuDataNotFoundException constructor.
     * @param string          $messageType Message type
     * @param \Throwable|null $previous    Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $messageType, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $messageType);
    }
}