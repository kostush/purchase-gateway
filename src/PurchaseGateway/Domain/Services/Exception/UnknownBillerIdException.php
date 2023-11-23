<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ConfigException;
use ProBillerNG\Logger\Exception as LoggerException;

/**
 * Class UnknownBillerIdException
 * @package ProBillerNG\PurchaseGateway\Exceptions
 */
class UnknownBillerIdException extends ConfigException
{
    protected $code = Code::UNKNOWN_BILLER_ID_EXCEPTION;

    /**
     * UnknownBillerIdException constructor.
     *
     * @param string          $billerId Biller id
     * @param \Throwable|null $previous Previous exception
     * @throws LoggerException
     */
    public function __construct(string $billerId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $billerId);
    }
}
