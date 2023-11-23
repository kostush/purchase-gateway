<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Repository\FailedEventPublishRepository;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\ServiceBus\Event;

class RetryFailedPurchaseProcessedPublishingCommandHandler
{
    /**
     * @var ServiceBusFactory
     */
    private $serviceBusFactory;

    /**
     * @var FailedEventPublishRepository
     */
    private $failedEventRepository;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * RetryFailedPurchaseProcessedPublishingCommandHandler constructor.
     * @param ServiceBusFactory            $serviceBusFactory            ServiceBusFactory
     * @param FailedEventPublishRepository $failedEventPublishRepository Failed event repository
     * @param EventStore                   $eventStore                   Event store
     *
     * @throws \Exception
     */
    public function __construct(
        ServiceBusFactory $serviceBusFactory,
        FailedEventPublishRepository $failedEventPublishRepository,
        EventStore $eventStore
    ) {
        $this->serviceBusFactory     = $serviceBusFactory;
        $this->failedEventRepository = $failedEventPublishRepository;
        $this->eventStore            = $eventStore;
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    public function execute(): void
    {
        $failedEvents = $this->failedEventRepository()->findBatch();

        /** @var FailedEventPublish $failedEvent */
        foreach ($failedEvents as $failedEvent) {
            try {
                $storedEvent = $this->eventStore()->getByAggregateIdAndType(
                    $failedEvent->aggregateId(),
                    PurchaseProcessed::class
                );

                $purchaseProcessedEvent = PurchaseProcessed::createFromJson($storedEvent->body());

                $serviceBus = $this->serviceBusFactory()->make();

                $event = Event::createWithCorrelationId($purchaseProcessedEvent, Log::getSessionId(), 'memberId');
                $serviceBus->publish($event);

                $failedEvent->markPublished();
            } catch (\AMQPException $exception) {
                // Do not increase retry count if amqp error. RabbitMQ issue.
                throw $exception;
            } catch (\Throwable $exception) {
                Log::error(
                    'Failed retry publish to RabbitMQ',
                    [
                        'aggregateId' => $failedEvent->aggregateId(),
                    ]
                );
                Log::logException($exception);
            }

            $failedEvent->increaseRetryCount();
            $this->failedEventRepository()->update($failedEvent);
        }
    }

    /**
     * @return ServiceBusFactory
     */
    public function serviceBusFactory(): ServiceBusFactory
    {
        return $this->serviceBusFactory;
    }

    /**
     * @return FailedEventPublishRepository
     */
    public function failedEventRepository(): FailedEventPublishRepository
    {
        return $this->failedEventRepository;
    }

    /**
     * @return EventStore
     */
    public function eventStore(): EventStore
    {
        return $this->eventStore;
    }
}
