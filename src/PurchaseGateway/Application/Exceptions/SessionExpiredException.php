<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class SessionExpiredException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class SessionExpiredException extends Exception
{
    protected $code = Code::SESSION_EXPIRED;
}
