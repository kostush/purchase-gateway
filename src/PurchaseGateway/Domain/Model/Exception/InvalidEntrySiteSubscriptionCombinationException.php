<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidEntrySiteSubscriptionCombinationException extends ValidationException
{
    protected $code = Code::INVALID_ENTRY_SITE_SUBSCRIPTION_COMBINATION;
}
