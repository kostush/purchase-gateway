<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class CannotMarkNonCrossSaleItemAsSelectedCrossSaleException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::NON_CROSS_SALE_ITEM_EXCEPTION;
}
