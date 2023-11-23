<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InvalidUserInfoEmail extends ValidationException
{
    protected $code = Code::INVALID_USER_INFO_EMAIL;
}
