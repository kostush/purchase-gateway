<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException;

class RebillFieldRequiredException extends ValidationException
{
    protected $code = Code::MGPG_REBILL_FIELD_REQUIRED;
}
