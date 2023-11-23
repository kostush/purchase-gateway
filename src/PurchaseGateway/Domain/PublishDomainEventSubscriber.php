<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use ProBillerNG\Base\Domain\DomainEvent;
use ProBillerNG\Base\Domain\DomainEventSubscriber;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\BaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Repository\FailedEventPublishRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\ServiceBus\Event as MessageEvent;

class PublishDomainEventSubscriber implements DomainEventSubscriber
{
    /** @var ServiceBusFactory */
    protected $serviceBusFactory;

    /** @var FailedEventPublishRepository */
    protected $failedEventPublishRepository;

    /**
     * PublishDomainEventSubscriber constructor.
     * @param ServiceBusFactory            $serviceBusFactory            Service bus factory
     * @param FailedEventPublishRepository $failedEventPublishRepository Repository
     */
    public function __construct(
        ServiceBusFactory $serviceBusFactory,
        FailedEventPublishRepository $failedEventPublishRepository
    ) {
        $this->serviceBusFactory            = $serviceBusFactory;
        $this->failedEventPublishRepository = $failedEventPublishRepository;
    }

    /**
     * @param DomainEvent $event Event
     * @return mixed|void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \Exception
     */
    public function handle(DomainEvent $event)
    {
        try {
            /** @var BaseEvent $event */
            $message = MessageEvent::create($event, 'memberId');

            $serviceBus = $this->serviceBusFactory->make();
            $serviceBus->publish($message);
        } catch (\Throwable $exception) {
            Log::error(
                'Cannot publish event to RabbitMQ',
                ['eventType' => get_class($event), 'aggregateId' => $event->aggregateId()]
            );
            Log::logException($exception);

            $this->handleFailedPublish($event);
        }
    }

    /**
     * @param DomainEvent $event Event
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function handleFailedPublish(DomainEvent $event): void
    {
        try {
            $failedPublishEvent = FailedEventPublish::create($event->aggregateId());
            $this->failedEventPublishRepository->add($failedPublishEvent);
        } catch (\Throwable $exception) {
            Log::critical(
                'Cannot add event to failed publish list',
                ['eventType' => get_class($event), 'aggregateId' => $event->aggregateId()]
            );
            Log::logException($exception);
        }
    }

    /**
     * @param mixed $event Event
     * @return bool
     */
    public function isSubscribedTo($event): bool
    {
        if ($event instanceof PurchaseProcessed) {
            return true;
        }

        return false;
    }
}
