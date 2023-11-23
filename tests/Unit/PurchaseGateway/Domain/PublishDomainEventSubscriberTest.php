<?php

declare(strict_types=1);

namespace Unit\PurchaseGateway\Domain;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\PublishDomainEventSubscriber;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\ServiceBus\ServiceBusFactory;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Repository\Doctrine\FailedEventPublishRepository;
use ProBillerNG\ServiceBus\ServiceBus;
use Tests\UnitTestCase;

class PublishDomainEventSubscriberTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_publish_a_given_event()
    {
        $serviceBusMock = $this->createMock(ServiceBus::class);
        $serviceBusMock->expects($this->once())->method('publish');

        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willReturn($serviceBusMock);

        /** @var MockObject|PublishDomainEventSubscriber $subscriberMock */
        $subscriberMock = $this->getMockBuilder(PublishDomainEventSubscriber::class)
            ->setConstructorArgs(
                [
                    $serviceBusFactory,
                    $this->createMock(FailedEventPublishRepository::class)
                ]
            )
            ->onlyMethods(['handleFailedPublish'])
            ->getMock();

        $subscriberMock->expects($this->never())->method('handleFailedPublish');

        $purchaseProcessedMock = $this->createMock(PurchaseProcessed::class);

        $subscriberMock->handle($purchaseProcessedMock);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_add_an_entry_in_failed_event_publish_table_if_cannot_publish_event()
    {
        $serviceBusFactory = $this->createMock(ServiceBusFactory::class);
        $serviceBusFactory->method('make')->willThrowException(new \Exception());

        $failedEventPublishRepository = $this->createMock(FailedEventPublishRepository::class);
        $failedEventPublishRepository->expects($this->once())->method('add');

        /** @var MockObject|PublishDomainEventSubscriber $subscriberMock */
        $subscriberMock = $this->getMockBuilder(PublishDomainEventSubscriber::class)
            ->setConstructorArgs(
                [
                    $serviceBusFactory,
                    $failedEventPublishRepository
                ]
            )
            ->onlyMethods([])
            ->getMock();

        $purchaseProcessedMock = $this->createMock(PurchaseProcessed::class);

        $subscriberMock->handle($purchaseProcessedMock);
    }
}
