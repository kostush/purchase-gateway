<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain;

use Illuminate\Queue\InvalidPayloadException;
use ProBillerNG\PurchaseGateway\Domain\Model\EventId;
use ProBillerNG\PurchaseGateway\Domain\StoredEvent;
use Tests\UnitTestCase;

class StoredEventTest extends UnitTestCase
{
    /**
     * obfuscated string
     */
    const OBFUSCATED_STRING = '*******';

    /**
     * @var string
     */
    private $json;

    /**
     * @throws \Exception
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->json = '{
                "aggregate_id":"b171a019-a8a6-45b8-be52-e94eb5d49bbe",
                "payment":
                    {"ccNumber":"'. $_ENV['ROCKETGATE_COMMON_CARD_NUMBER'] . '","cvv":111,"cardExpirationMonth":"11","cardExpirationYear":"2021"}
            }';
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     * @throws InvalidPayloadException
     */
    public function create_should_return_exception_when_incorrect_payload_data_is_provided()
    {
        $this->expectException(InvalidPayloadException::class);

        $this->createStoredEvent('{ value: "test"');
    }

    /**
     * @test
     * @return StoredEvent
     * @throws \Exception
     */
    public function create_should_return_stored_event_when_correct_data_is_provided(): StoredEvent
    {
        $storedEvent = $this->createStoredEvent($this->json);

        $this->assertInstanceOf(StoredEvent::class, $storedEvent);

        return $storedEvent;
    }

    /**
     * @depends create_should_return_stored_event_when_correct_data_is_provided
     * @test
     * @param StoredEvent $storedEvent Stored event
     * @return void
     * @throws \Exception
     */
    public function stored_event_should_have_obfuscated_card_number(StoredEvent $storedEvent)
    {
        $returnedPayload = json_decode($storedEvent->eventBody(), true);
        $this->assertSame(self::OBFUSCATED_STRING, $returnedPayload['payment']['ccNumber']);
    }

    /**
     * @depends create_should_return_stored_event_when_correct_data_is_provided
     * @test
     * @param StoredEvent $storedEvent Stored event
     * @return void
     * @throws \Exception
     */
    public function stored_event_should_have_cvv_obfuscated(StoredEvent $storedEvent)
    {
        $returnedPayload = json_decode($storedEvent->eventBody(), true);
        $this->assertSame(self::OBFUSCATED_STRING, $returnedPayload['payment']['cvv']);
    }

    /**
     * @param string $json Json data
     * @return StoredEvent
     * @throws \Exception
     */
    private function createStoredEvent(string $json): StoredEvent
    {
        $storedEvent = new StoredEvent(
            EventId::create(),
            'b171a019-a8a6-45b8-be52-e94eb5d49bbe',
            'ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed',
            new \DateTimeImmutable(),
            $json
        );

        return $storedEvent;
    }
}
