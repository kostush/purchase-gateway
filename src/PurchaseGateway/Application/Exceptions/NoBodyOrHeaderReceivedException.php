<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class NoBodyOrHeaderReceivedException extends Exception
{
    protected $code = Code::NO_BODY_OR_HEADER_RECEIVED_EXCEPTION;
}
