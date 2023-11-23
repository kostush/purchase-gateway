<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Application\Services;

use Doctrine\ORM\EntityManagerInterface;
use ProBillerNG\Base\Application\Services\TransactionalSession;

class DoctrineSession implements TransactionalSession
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager Entity Manager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritDoc}
     *
     * @param callable $operation Operation
     * @return mixed
     * @throws \Throwable
     */
    public function executeAtomically(callable $operation)
    {
        return $this->entityManager->transactional($operation);
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function flush()
    {
        $this->entityManager->flush();
    }
}
