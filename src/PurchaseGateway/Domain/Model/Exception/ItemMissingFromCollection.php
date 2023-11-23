<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class ItemMissingFromCollection extends ConfigException
{
    protected $code = Code::ITEM_IS_MISSING_FROM_COLLECTION;
}
