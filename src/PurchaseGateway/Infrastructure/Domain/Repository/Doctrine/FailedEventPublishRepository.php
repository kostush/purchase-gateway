<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine;

use Doctrine\ORM\EntityRepository;
use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;
use ProBillerNG\PurchaseGateway\Domain\Repository\FailedEventPublishRepository as FailedEventPublishRepositoryInterface;

class FailedEventPublishRepository extends EntityRepository implements FailedEventPublishRepositoryInterface
{
    /**
     * @return mixed
     */
    public function findBatch()
    {
        $query = $this->createQueryBuilder('e');

        $query->where('e.published = 0');
        $query->andWhere('e.retries < 5');

        $query->orderBy('e.lastAttempted', 'ASC');
        $query->setMaxResults(100);

        return $query->getQuery()->getResult();
    }

    /**
     * @param FailedEventPublish $failedEventPublish Entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function add(FailedEventPublish $failedEventPublish): void
    {
        $this->getEntityManager()->persist($failedEventPublish);
        $this->getEntityManager()->flush();
    }

    /**
     * @param FailedEventPublish $failedEventPublish Entity
     *
     * @return void
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function update(FailedEventPublish $failedEventPublish): void
    {
        $this->add($failedEventPublish);
    }
}
