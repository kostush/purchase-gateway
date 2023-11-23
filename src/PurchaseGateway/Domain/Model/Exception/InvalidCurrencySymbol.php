<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidCurrencySymbol extends ValidationException
{
    protected $code = Code::INVALID_CURRENCY_SYMBOL;
}
