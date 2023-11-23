<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;

class CreditCardIsBlacklisted extends ValidationException implements IncreasePurchaseAttempts, Returns400Code
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::CREDIT_CARD_IS_BLACKLISTED;
}
