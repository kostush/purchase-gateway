<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector;

use ProBillerNG\Projection\Infrastructure\Domain\Repository\Doctrine\DoctrineProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Projector\ProjectedItem\BusinessGroup;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BusinessGroupRepository;

class DoctrineBusinessGroupProjectionRepository extends DoctrineProjectionRepository implements BusinessGroupRepository
{
    /**
     * @param string $businessGroupId Business group id
     * @return BusinessGroup
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findBusinessGroupById(string $businessGroupId): BusinessGroup
    {
        // This flush is necessary to save in the database the BusinessGroup before searching for it below
        $this->getEntityManager()->flush();

        return $this->findOneBy(['businessGroupId' => $businessGroupId]);
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\DBALException
     */
    public function resetProjection(): void
    {
        $connection = $this->getEntityManager()->getConnection();
        $platform   = $connection->getDatabasePlatform();
        $connection->executeUpdate(
            $platform->getTruncateTableSQL(
                $this->getClassMetadata()->getTableName(),
                true
            )
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
}
