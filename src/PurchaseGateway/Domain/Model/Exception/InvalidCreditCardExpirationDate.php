<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidCreditCardExpirationDate extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::CREDIT_CARD_INVALID_EXPIRY_DATE;
}
