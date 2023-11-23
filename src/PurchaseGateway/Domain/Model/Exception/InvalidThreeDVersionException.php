<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class InvalidThreeDVersionException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::INVALID_THREE_D_VERSION;

    /**
     * InvalidAmountException constructor.
     * @param int             $parameter Parameter
     * @param \Throwable|null $previous  Previous Exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(int $parameter, \Throwable $previous = null)
    {
        parent::__construct($previous, $parameter);
    }
}
