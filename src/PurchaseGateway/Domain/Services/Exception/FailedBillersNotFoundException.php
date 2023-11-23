<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;

class FailedBillersNotFoundException extends NotFoundException
{
    protected $code = Code::FAILED_BILLERS_NOT_FOUND;
}
