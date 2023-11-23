<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Application\Services\CreatePurchaseIntegrationEvent\RetryFailedPurchaseProcessedPublishingCommandHandler;
use ProBillerNG\PurchaseGateway\Domain\EventStore;
use ProBillerNG\PurchaseGateway\Domain\FailedEventPublish;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Repository\FailedEventPublishRepository;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\ServiceBus\ServiceBus;
use Tests\UnitTestCase;

class RetryFailedPurchaseProcessedPublishingCommandHandlerTest extends UnitTestCase
{
    /**
     * @var FailedEventPublish
     */
    private $failedEvent;

    /**
     * @var MockObject|FailedEventPublishRepository
     */
    private $failedEventRepository;

    /**
     * @var MockObject|EventStore
     */
    private $eventStore;

    /**
     * @var MockObject|ServiceBusFactory
     */
    private $serviceBusFactory;

    /**
     * @return void
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->failedEvent = FailedEventPublish::create($this->faker->uuid);

        $this->failedEventRepository = $this->createMock(FailedEventPublishRepository::class);
        $this->failedEventRepository->method('findBatch')->willReturn([$this->failedEvent]);

        $storedEventMock = $this->createMock(StoredEvent::class);
        $storedEventMock->method('body')->willReturn(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData())
        );

        $this->eventStore = $this->createMock(EventStore::class);
        $this->eventStore->method('getByAggregateIdAndType')->willReturn($storedEventMock);

        $serviceBus = $this->createMock(ServiceBus::class);

        $this->serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $this->serviceBusFactory->method('make')->willReturn($serviceBus);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_retrieve_stored_purchase_processed_with_aggregate_id_of_failed_event()
    {
        $storedEventMock = $this->createMock(StoredEvent::class);
        $storedEventMock->method('body')->willReturn(
            json_encode($this->createPurchaseProcessedWithRocketgateNewPaymentEventData())
        );

        $this->eventStore->expects($this->once())->method('getByAggregateIdAndType')->with(
            $this->failedEvent->aggregateId(),
            PurchaseProcessed::class
        )->willReturn($storedEventMock);

        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willReturn($this->eventStore);
        $handlerMock->method('serviceBusFactory')->willReturn($this->serviceBusFactory);

        $handlerMock->execute();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_publish_the_event()
    {
        $serviceBus = $this->createMock(ServiceBus::class);
        $serviceBus->expects($this->once())->method('publish');

        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBus);

        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willReturn($this->eventStore);
        $handlerMock->method('serviceBusFactory')->willReturn($serviceBusFactory);

        $handlerMock->execute();
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_mark_failed_event_as_published_if_successful()
    {
        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willReturn($this->eventStore);
        $handlerMock->method('serviceBusFactory')->willReturn($this->serviceBusFactory);

        $handlerMock->execute();

        $this->assertTrue($this->failedEvent->published());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_increase_retry_count_on_failed_event()
    {
        $originalRetries = $this->failedEvent->retries();

        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willReturn($this->eventStore);
        $handlerMock->method('serviceBusFactory')->willReturn($this->serviceBusFactory);

        $handlerMock->execute();

        $this->assertEquals(($originalRetries + 1), $this->failedEvent->retries());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_increase_retry_count_if_exception_encountered()
    {
        $originalRetries = $this->failedEvent->retries();

        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willThrowException(new \Exception());

        $handlerMock->execute();

        $this->assertEquals(($originalRetries + 1), $this->failedEvent->retries());
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_not_increase_retry_count_if_amqp_exception_encountered()
    {
        $this->expectException(\AMQPException::class);

        $originalRetries = $this->failedEvent->retries();

        /** @var MockObject|RetryFailedPurchaseProcessedPublishingCommandHandler $handlerMock */
        $handlerMock = $this->getMockBuilder(RetryFailedPurchaseProcessedPublishingCommandHandler::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['execute'])
            ->getMock();
        $handlerMock->method('failedEventRepository')->willReturn($this->failedEventRepository);
        $handlerMock->method('eventStore')->willReturn($this->eventStore);
        $handlerMock->method('serviceBusFactory')->willThrowException(new \AMQPException());

        $handlerMock->execute();

        $this->assertEquals($originalRetries, $this->failedEvent->retries());
    }
}
