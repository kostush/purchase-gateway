<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use LaravelDoctrine\ORM\IlluminateRegistry;

trait ClearConnections
{
    /**
     * Clear all singletons for the purpose of avoiding contamination
     * @return void
     */
    public function clearConnections()
    {
        /** @var IlluminateRegistry $doctrine */
        $doctrine = app('registry');
        /** @var EntityManager $entityManager */
        foreach ($doctrine->getManagers() as $name => $entityManager) {
            $entityManager->close();
        };
        /** @var Connection $connection */
        foreach ($doctrine->getConnections() as $name => $connection) {
            $connection->close();
        };
    }
}
