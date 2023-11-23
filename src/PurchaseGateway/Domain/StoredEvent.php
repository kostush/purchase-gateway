<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

use Illuminate\Queue\InvalidPayloadException;
use ProBillerNG\Projection\Domain\ItemToProject;
use ProBillerNG\Projection\Domain\ItemToWorkOn;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\BaseEvent;
use ProBillerNG\PurchaseGateway\Domain\Model\EventId;

class StoredEvent extends BaseEvent implements ObfuscatedData, ItemToWorkOn
{
    /**
     * @var EventId
     */
    protected $eventId;

    /**
     * @var string
     */
    protected $eventBody;

    /**
     * @var string
     */
    protected $typeName;

    /**
     * @param EventId            $eventId      EventId
     * @param string             $aggregateId  Aggregate Id
     * @param string             $aTypeName    Type Name
     * @param \DateTimeImmutable $anOccurredOn Occurred on
     * @param string             $anEventBody  Event body
     * @throws \Exception
     */
    public function __construct(
        EventId $eventId,
        string $aggregateId,
        string $aTypeName,
        \DateTimeImmutable $anOccurredOn,
        string $anEventBody
    ) {
        parent::__construct($aggregateId, $anOccurredOn);
        $this->eventId     = $eventId;
        $this->aggregateId = $aggregateId;
        $this->eventBody   = $this->obfuscateEventBody($anEventBody);
        $this->typeName    = $aTypeName;
    }

    /**
     * @return EventId
     */
    public function eventId(): EventId
    {
        return $this->eventId;
    }

    /**
     * @return string
     */
    public function eventBody(): string
    {
        return $this->eventBody;
    }

    /**
     * @return string
     */
    public function typeName(): string
    {
        return $this->typeName;
    }

    /**
     * Overwrite event body
     * @param string $eventBody Event body as json
     * @return void
     */
    public function overwriteEventBody(string $eventBody): void
    {
        $this->eventBody = $eventBody;
    }

    /**
     * @param string $eventBody Event Body json
     * @return string
     */
    private function obfuscateEventBody(string $eventBody): string
    {
        $payload = json_decode($eventBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw  new InvalidPayloadException();
        }

        if (!isset($payload['payment'])) {
            return $eventBody;
        }

        $paymentData = $payload['payment'];

        $obfuscationKeys = [
            'ccNumber',
            'cvv'
        ];

        foreach ($obfuscationKeys as $obfuscateKey) {
            if (!empty($paymentData[$obfuscateKey])) {
                $paymentData[$obfuscateKey] = self::OBFUSCATED_STRING;
            }
        }

        $payload['payment'] = $paymentData;

        return json_encode($payload);
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return (string) $this->eventId();
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->eventBody();
    }
}
