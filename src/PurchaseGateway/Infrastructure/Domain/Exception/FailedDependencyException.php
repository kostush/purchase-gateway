<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception;

use ProBillerNG\Logger\Exception as LoggerException;
use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class FailedDependencyException extends Exception
{
    protected $code = Code::FAILED_DEPENDENCY;

    /**
     * FailedDependencyException constructor.
     *
     * @param string          $service  Service Failed
     * @param \Throwable|null $previous Previous Exception
     *
     * @throws LoggerException
     */
    public function __construct(string $service, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $service);
    }
}
