<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping;
use JMS\Serializer\SerializerInterface;
use ProBillerNG\PurchaseGateway\Domain\Event;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\EventTracker;
use ProBillerNG\PurchaseGateway\Domain\Model\EventId;
use ProBillerNG\PurchaseGateway\Domain\Repository\EventTrackerRepository;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;

class DoctrineEventTracker extends EntityRepository implements EventTrackerRepository
{
    /**
     * @param string $type Type
     * @return mixed
     */
    public function findEventTrackerBy(string $type)
    {
        return $this->findOneBy(['eventTrackerType' => $type]);
    }
}
