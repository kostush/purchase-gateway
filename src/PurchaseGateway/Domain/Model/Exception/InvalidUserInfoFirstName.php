<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidUserInfoFirstName extends ValidationException
{
    protected $code = Code::INVALID_USER_INFO_FIRST_NAME;
}
