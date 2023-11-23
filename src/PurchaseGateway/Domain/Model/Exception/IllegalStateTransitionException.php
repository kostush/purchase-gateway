<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class IllegalStateTransitionException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::ILLEGAL_STATE_TRANSITION_EXCEPTION;
}
