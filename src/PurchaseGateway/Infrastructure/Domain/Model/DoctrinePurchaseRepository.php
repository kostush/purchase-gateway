<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Model;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\ORMInvalidArgumentException;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Purchase;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseId;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Exception\RepositoryException;

class DoctrinePurchaseRepository extends EntityRepository implements PurchaseRepository
{
    /**
     * @param Purchase $purchase Purchase
     * @return void
     * @throws RepositoryException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function add(Purchase $purchase)
    {
        try {
            $this->getEntityManager()->persist($purchase);
        } catch (ORMException | ORMInvalidArgumentException $e) {
            throw new RepositoryException('Unable to store purchase entity', 0, $e);
        }

        Log::info('Purchase persisted', ['purchaseId' => $purchase->getEntityId()]);
    }

    /**
     * @param PurchaseId $id Purchase Id.
     *
     * @return Purchase|null
     */
    public function findById(PurchaseId $id): ?Purchase
    {
        return $this->find((string) $id);
    }

    /**
     * @param Purchase $purchase Purchase
     * @return void
     * @throws RepositoryException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function addOrUpdatePurchase(Purchase $purchase): void
    {
        $this->add($purchase);
    }
}
