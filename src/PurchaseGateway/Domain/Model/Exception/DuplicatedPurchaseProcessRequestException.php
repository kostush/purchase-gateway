<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class DuplicatedPurchaseProcessRequestException extends ValidationException
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::DUPLICATED_PURCHASE_PROCESS_REQUEST;
}
