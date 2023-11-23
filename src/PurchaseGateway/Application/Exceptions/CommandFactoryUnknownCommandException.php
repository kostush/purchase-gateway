<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

/**
 * Class CommandFactoryUnknownCommandException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class CommandFactoryUnknownCommandException extends Exception
{
    protected $code = Code::COMMAND_FACTORY_UNKNOWN_COMMAND_EXCEPTION;

    /**
     * CommandFactoryUnknownCommandException constructor.
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
