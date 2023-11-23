<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;
use ProBillerNG\PurchaseGateway\Domain\Returns400Code;

class PaymentTemplateValidationException extends ValidationException implements
    IncreasePurchaseAttempts,
    Returns400Code
{

}
