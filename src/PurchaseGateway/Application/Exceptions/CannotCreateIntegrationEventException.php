<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class CannotCreateIntegrationEventException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CannotCreateIntegrationEventException extends Exception
{
    protected $code = Code::CREATE_INTEGRATION_EVENT_EXCEPTION;
}
