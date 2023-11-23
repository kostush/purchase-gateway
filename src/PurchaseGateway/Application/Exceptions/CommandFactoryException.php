<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class CommandFactoryException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CommandFactoryException extends Exception
{
    protected $code = Code::COMMAND_FACTORY_EXCEPTION;

    /**
     * CommandFactoryException constructor.
     *
     * @param string          $messageType Message type
     * @param \Throwable|null $previous    Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $messageType, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $messageType);
    }
}
