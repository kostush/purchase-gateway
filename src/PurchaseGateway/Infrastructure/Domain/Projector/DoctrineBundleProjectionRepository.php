<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Projector;

use ProBillerNG\Projection\Infrastructure\Domain\Repository\Doctrine\DoctrineProjectionRepository;
use ProBillerNG\PurchaseGateway\Domain\Model\AddonId;
use ProBillerNG\PurchaseGateway\Domain\Model\Bundle;
use ProBillerNG\PurchaseGateway\Domain\Model\BundleId;
use ProBillerNG\PurchaseGateway\Domain\Projector\Repository\BundleRepository;
use ProBillerNG\PurchaseGateway\Domain\Repository\BundleRepositoryReadOnly;

class DoctrineBundleProjectionRepository extends DoctrineProjectionRepository implements BundleRepository, BundleRepositoryReadOnly
{
    /**
     * @param string $addonId Addon Id
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findByAddonId(string $addonId): array
    {
        // This flush is necessary to save in the database the Addon before searching for it below
        $this->getEntityManager()->flush();

        return $this->findBy(['addonId' => $addonId]);
    }

    /**
     * @param string $bundleId Bundle Id
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findBundleById(string $bundleId): array
    {
        // This flush is necessary to save in the database the Bundle before searching for it below
        $this->getEntityManager()->flush();

        return $this->findBy(['bundleId' => $bundleId]);
    }

    /**
     * @param BundleId $bundleId bundle id
     * @param AddonId  $addonId  addon id
     * @return Bundle
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function findBundleByBundleAddon(BundleId $bundleId, AddonId $addonId): ?Bundle
    {
        $bundles = $this->findBy(
            [
                'bundleId' => (string) $bundleId,
                'addonId'  => (string) $addonId
            ]
        );

        if (count($bundles) == 0) {
            return null;
        }

        return $bundles[0];
    }

    /**
     * @param array $bundleIds Bundle Ids
     * @param array $addonIds  Addon Ids
     * @return Bundle[]
     * @throws \Exception
     */
    public function findBundleByIds(array $bundleIds, array $addonIds): array
    {
        $query = $this->createQueryBuilder('b')->where('b.bundleId in (:ids)')
            ->andWhere('b.addonId in (:addonIds)')
            ->setParameter('ids', $bundleIds)
            ->setParameter('addonIds', $addonIds);

        $bundles = $query->getQuery()->getResult();

        $resultedBundles = [];

        array_map(
            function (Bundle $bundle) use (& $resultedBundles) {
                $resultedBundles[(string) $bundle->bundleId()] = $bundle;
            },
            $bundles
        );

        return $resultedBundles;
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
