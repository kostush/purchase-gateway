<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services;

class IntegrationEventPublisher
{
    /** @var array|IntegrationEventSubscriber[] */
    private $subscribers;

    /** @var IntegrationEventPublisher */
    private static $instance = null;

    /**
     * @return array|IntegrationEventSubscriber[]
     */
    public function subscribers()
    {
        return $this->subscribers;
    }

    /**
     * @return IntegrationEventPublisher
     */
    public static function instance(): self
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * DomainEventPublisher constructor.
     */
    private function __construct()
    {
        $this->subscribers = [];
    }

    /**
     * @return void
     */
    public function __clone()
    {
        throw new \BadMethodCallException('Clone is not supported');
    }

    /**
     * Added to help with testing only due to the singleton
     * @return void
     */
    public static function tearDown()
    {
        static::$instance = null;
    }

    /**
     * @param IntegrationEventSubscriber $eventSubscriber Event Subscriber
     * @return void
     */
    public function subscribe(IntegrationEventSubscriber $eventSubscriber): void
    {
        $this->subscribers[] = $eventSubscriber;
    }

    /**
     * @param IntegrationEvent $event Event
     * @return void
     */
    public function publish(IntegrationEvent $event): void
    {
        foreach ($this->subscribers as $subscriber) {
            if ($subscriber->isSubscribedTo($event)) {
                $subscriber->handle($event);
            }
        }
    }
}
