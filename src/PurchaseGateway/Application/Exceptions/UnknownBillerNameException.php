<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Exceptions;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ConfigException;

class UnknownBillerNameException extends ConfigException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::UNKNOWN_BILLER_NAME_EXCEPTION;

    /**
     * UnknownBillerNameException constructor.
     *
     * @param string          $billerName Biller name
     * @param \Throwable|null $previous   Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $billerName, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $billerName);
    }
}
