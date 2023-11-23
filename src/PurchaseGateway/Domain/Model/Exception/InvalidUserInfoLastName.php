<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidUserInfoLastName extends ValidationException
{
    protected $code = Code::INVALID_USER_INFO_LAST_NAME;
}
