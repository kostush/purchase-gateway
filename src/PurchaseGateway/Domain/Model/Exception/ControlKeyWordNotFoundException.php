<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class ControlKeyWordNotFoundException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::NETBILLING_CONTROL_KEYWORD_NOT_FOUND;

    /**
     * ControlKeyWordNotFoundException constructor.
     * @param string          $accountId account id
     * @param \Throwable|null $previous  previous error
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $accountId, ?\Throwable $previous = null)
    {
        parent::__construct($previous, $accountId);
    }
}
