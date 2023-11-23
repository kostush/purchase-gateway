<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\ItemRepositoryReadOnly;
use ProBillerNG\PurchaseGateway\Domain\Model\ProcessedBundleItem;

class DoctrineItemRepository extends EntityRepository implements ItemRepositoryReadOnly
{
    /**
     * @param string $id Item identifier
     * @return ProcessedBundleItem
     */
    public function findById(string $id): ?ProcessedBundleItem
    {
        return $this->find($id);
    }
}
