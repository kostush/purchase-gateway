<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector;

use ProBillerNG\Projection\Domain\ProjectedItem;
use ProBillerNG\Projection\Infrastructure\Domain\Repository\Doctrine\DoctrineProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\AddonRepository;

class DoctrineAddonProjectionRepository extends DoctrineProjectionRepository implements AddonRepository
{
    /**
     * @param ProjectedItem $projectedItem
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function delete(ProjectedItem $projectedItem): void
    {
        $this->getEntityManager()->flush();

        $projectedItem = $this->mergeIfDetached($projectedItem);

        parent::delete($projectedItem);
    }

    /**
     * @param array $ids Ids
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findByIds(array $ids): array
    {
        $this->getEntityManager()->flush();

        $query = $this->createQueryBuilder('a')->where('a.addonId in (:ids)')
            ->setParameter('ids', $ids);

        return $query->getQuery()->getResult();
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resetProjection(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform   = $connection->getDatabasePlatform();
        $connection->executeUpdate($platform->getTruncateTableSQL(
            $this->getClassMetadata()->getTableName(),
            true)
        );
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function deleteProjection(): void
    {
        $this->resetProjection();
    }

    /**
     * @param ProjectedItem $dataObject
     * @return ProjectedItem
     * @throws \Doctrine\ORM\ORMException
     */
    public function mergeIfDetached(ProjectedItem $dataObject): ProjectedItem
    {
        if ($this->getEntityManager()->getUnitOfWork()->getEntityState($dataObject) == \Doctrine\ORM\UnitOfWork::STATE_DETACHED) {
            $dataObject = $this->getEntityManager()->merge($dataObject);
        }

        return $dataObject;
    }
}
