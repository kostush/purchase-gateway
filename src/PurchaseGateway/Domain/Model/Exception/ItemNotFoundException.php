<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Exception;

use ProBillerNG\PurchaseGateway\Code;

class ItemNotFoundException extends NotFoundException
{
    protected $code = Code::ITEM_NOT_FOUND;

    /**
     * ItemNotFoundException constructor.
     * @param string          $itemId   Item Id
     * @param \Throwable|null $previous Previous exception
     * @throws \ProBillerNG\Logger\Exception
     */
    public function __construct(string $itemId, \Throwable $previous = null)
    {
        parent::__construct($previous, $itemId);
    }
}
