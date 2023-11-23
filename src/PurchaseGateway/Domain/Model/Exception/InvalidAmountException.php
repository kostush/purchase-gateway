<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidAmountException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_AMOUNT;

    /**
     * InvalidAmountException constructor.
     * @param string          $parameter Parameter
     * @param \Throwable|null $previous  Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $parameter, \Throwable $previous = null)
    {
        parent::__construct($previous, $parameter);
    }
}
