<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ConfigException;

class InvalidForceCascadeException extends ConfigException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_FORCE_CASCADE_EXCEPTION;

    /**
     * InvalidForceCascadeException constructor.
     * @param string          $forceCascade Force cascade.
     * @param \Throwable|null $previous     Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $forceCascade, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $forceCascade);
    }
}