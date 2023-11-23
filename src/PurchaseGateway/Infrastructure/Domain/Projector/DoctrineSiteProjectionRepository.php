<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector;

use ProBillerNG\Logger\Log;
use ProBillerNG\Projection\Infrastructure\Domain\Repository\Doctrine\DoctrineProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\SiteRepository;
use ProBillerNG\PurchaseGateway\Domain\Repository\SiteRepositoryReadOnly;

class DoctrineSiteProjectionRepository extends DoctrineProjectionRepository implements SiteRepository, SiteRepositoryReadOnly
{
    /**
     * @param string $siteId
     *
     * @return Site|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function findSiteById(string $siteId): ?Site
    {
        Log::warning('SiteRepository Retrieving site from deprecated database', ['siteId' => $siteId]);
        // This flush is necessary to save in the database the Site before searching for it below
        $this->getEntityManager()->flush();

        return $this->findOneBy(['siteId' => $siteId]);
    }

    /**
     * @param string $siteId Site id
     * @return Site|null
     */
    public function findSite(string $siteId): ?Site
    {
        return $this->findOneBy(['siteId' => $siteId]);
    }

    /**
     * @param string $businessGroupId Business group Id
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findSitesByBusinessGroupId(string $businessGroupId): array
    {
        // This flush is necessary to update in the database the Business Group before searching for it below
        $this->getEntityManager()->flush();

        return $this->findBy(['businessGroupId' => $businessGroupId]);
    }

    /**
     * @param array $criteria Criteria
     * @return int
     */
    public function countAll(array $criteria = []): int
    {
        return $this->count($criteria);
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
