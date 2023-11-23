<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services\Exception;

use ProBillerNG\PurchaseGateway\Code;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\NotFoundException;

class InitializedItemsCollectionNotFoundException extends NotFoundException
{
    protected $code = Code::INITIALIZED_ITEM_COLLECTION_NOT_FOUND;
}
