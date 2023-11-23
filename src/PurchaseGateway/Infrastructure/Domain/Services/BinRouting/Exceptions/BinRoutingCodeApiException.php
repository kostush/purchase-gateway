<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\BinRouting\Exceptions;

use ProBillerNG\PurchaseGateway\Code;

class BinRoutingCodeApiException extends BinRoutingException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::BIN_ROUTING_API_ERROR_EXCEPTION;
}
