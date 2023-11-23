<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class InitPurchaseInfoNotFoundException extends NotFoundException
{
    protected $code = Code::INIT_INFO_NOT_FOUND_ON_SESSION;
}
