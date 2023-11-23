<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class DomainEventConversionException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class DomainEventConversionException extends Exception
{
    protected $code = Code::DOMAIN_EVENT_CONVERSION_FAILED;
}
