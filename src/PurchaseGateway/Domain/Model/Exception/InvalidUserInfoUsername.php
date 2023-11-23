<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidUserInfoUsername extends ValidationException
{
    protected $code = Code::INVALID_USER_INFO_USERNAME;
}
