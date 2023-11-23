<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

use ProBillerNG\Base\Domain\DomainEvent;
use ProBillerNG\Base\Domain\DomainEventPublisher;
use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\Base\Application\Services\CommandHandler;
use ProBillerNG\Base\Application\Services\ExposeDomainEvents;
use ProBillerNG\Base\Application\Services\TransactionalSession;
use ProBillerNG\PurchaseGateway\Domain\IncreasePurchaseAttempts;

class TransactionalCommandHandler implements CommandHandler
{
    /**
     * @var TransactionalSession
     */
    private $session;

    /**
     * @var CommandHandler
     */
    private $handler;

    /**
     * @param CommandHandler       $handler Command Handler
     * @param TransactionalSession $session Transactional session
     */
    public function __construct(CommandHandler $handler, TransactionalSession $session)
    {
        $this->session = $session;
        $this->handler = $handler;
    }

    /**
     * Executes command atomically
     *
     * @param Command $command Command
     * @return mixed
     */
    public function execute(Command $command)
    {
        $operation = function () use ($command) {
            try {
                $result = $this->handler->execute($command);
            } catch (IncreasePurchaseAttempts $e) {
                $result = $e;
            }

            // Firing the events
            // I chose the approach to send in the same transactional context, so before persisting
            // @see https://lostechies.com/jimmybogard/2014/05/13/a-better-domain-events-pattern/
            if ($this->handler instanceof ExposeDomainEvents) {
                /** @var DomainEvent $event */
                foreach ($this->handler->events() as $event) {
                    DomainEventPublisher::instance()->publish($event);
                }
                $this->handler->clearEvents();
            }

            if ($this->handler instanceof ExposeIntegrationEvents) {
                /** @var IntegrationEvent $event */
                foreach ($this->handler->integrationEvents() as $event) {
                    IntegrationEventPublisher::instance()->publish($event);
                }
                $this->handler->clearIntegrationEvents();
            }

            return $result;
        };

        return $this->session->executeAtomically($operation);
    }
}
