<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use ProBillerNG\PurchaseGateway\Domain\Event;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\Model\EventId;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;

class DoctrineEventStore extends EntityRepository implements EventStore
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * DoctrineEventStore constructor.
     * @param EntityManagerInterface $em         The entity manager
     * @param Mapping\ClassMetadata  $class      The class metadata used for the mapping
     * @param SerializerInterface    $serializer The serializer we will use before saving
     */
    public function __construct(
        EntityManagerInterface $em,
        Mapping\ClassMetadata $class,
        SerializerInterface $serializer
    ) {
        parent::__construct($em, $class);
        $this->serializer = $serializer;
    }

    /**
     * @param Event $anEvent The event I want to append
     * @return mixed|void
     * @throws \Doctrine\ORM\ORMException|\Exception
     */
    public function append(Event $anEvent)
    {
        $storedEvent = new StoredEvent(
            EventId::create(),
            $anEvent->aggregateId(),
            get_class($anEvent),
            $anEvent->occurredOn(),
            $this->serializer->serialize(
                $anEvent,
                'json',
                SerializationContext::create()->setSerializeNull(true)
            )
        );

        $this->getEntityManager()->persist($storedEvent);
        $this->getEntityManager()->flush();
    }

    /**
     * @param \DateTimeImmutable|null $anEventDate Event date
     * @param array                   $eventType   Event type
     * @param int                     $batchSize   Batch Size
     * @return mixed
     */
    public function nextBatchOfEventsByTypeSince(
        ?\DateTimeImmutable $anEventDate,
        array $eventType,
        int $batchSize
    ) {
        $query = $this->createQueryBuilder('e');

        $query->where('e.typeName IN (:eventType)');
        $parameters = ['eventType' => $eventType];

        if ($anEventDate) {
            $query->andWhere('e.occurredOn > :eventDate');
            $parameters['eventDate'] = $anEventDate->format('Y-m-d H:i:s.u');
        }

        $query->setParameters($parameters);

        $query->orderBy('e.occurredOn');
        $query->setMaxResults($batchSize);

        return $query->getQuery()->getResult();
    }

    /**
     * @param string $aggregateId Aggregate ids
     * @param string $eventType   Event type
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByAggregateIdAndType(string $aggregateId, string $eventType)
    {
        $query = $this->createQueryBuilder('e');

        $query->where('e.aggregateId = :aggregateId');
        $query->andWhere('e.typeName = :typeName');
        $parameters = [
            'aggregateId' => $aggregateId,
            'typeName'    => $eventType
        ];

        $query->setParameters($parameters);

        return $query->getQuery()->getSingleResult();
    }
}
