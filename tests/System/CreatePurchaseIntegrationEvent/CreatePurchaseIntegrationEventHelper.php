<?php
declare(strict_types=1);

namespace Tests\System\CreatePurchaseIntegrationEvent;

use Doctrine\ORM\QueryBuilder;

trait CreatePurchaseIntegrationEventHelper
{
    /**
     * @return void
     * @throws \Exception
     */
    public function updateLedgerPositionToNow(): void
    {
        /** @var QueryBuilder $qb */
        $qb    = app('em')->createQueryBuilder();
        $query = $qb->update('ProBillerNG\Projection\Domain\Projectionist\ProjectionPositionLedger', 'e')
            ->set('e.position', '?1')
            ->where('e.origin <> ?2 ' )
            ->setParameter('1', (new \DateTimeImmutable())->format('Y-m-d H:i:s.u'))
            ->setParameter('2', 'projector')
            ->getQuery();

        $query->execute();
    }

    /**
     * @param string $aggregateId The aggregate id
     * @param string $eventType   The event to search for
     *
     * @return int
     *
     * @throws \Exception
     */
    public function countStoredEvent(string $aggregateId, string $eventType): int
    {
        /** @var QueryBuilder $qb */
        $qb    = app('em')->createQueryBuilder();
        $query = $qb->select('count(1)')
            ->from('ProBillerNG\PurchaseGateway\Domain\StoredEvent', 'e')
            ->where('e.aggregateId = ?1 AND e.typeName = ?2')
            ->setParameter('1', $aggregateId)
            ->setParameter('2', $eventType);

        return (int) $query->getQuery()->getSingleScalarResult();
    }

    /**
     * @param mixed $response The init response
     *
     * @return string
     */
    public function retrieveTokenFromInitResponse($response): string
    {
        $response->seeHeader('X-Auth-Token');
        return (string) $this->response->headers->get('X-Auth-Token');
    }

    /**
     * @param mixed $response The purchase response
     *
     * @return string
     */
    public function retrievePurchaseIdFromProcessResponse($response): string
    {
        $data = json_decode($response->response->content(), true);

        return $data['purchaseId']??"";
    }
}
