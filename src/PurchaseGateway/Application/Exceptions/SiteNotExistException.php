<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class SessionExpiredException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class SiteNotExistException extends Exception
{
    protected $code = Code::SITE_NOT_EXIST_EXCEPTION;
}
