<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Exception;

class PurchaseEntityCannotBeCreatedException extends Exception
{
    /**
     * @var int $code Error code
     */
    protected $code = Code::PURCHASE_ENTITY_CANNOT_BE_CREATED;
}
