<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\TimerPendingPurchases;

use ProBillerNG\Projection\Domain\ItemToWorkOn;

class PurchaseProcessedSession implements ItemToWorkOn
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var \DateTimeImmutable
     */
    private $occurredOn;

    /**
     * @var string
     */
    private $typeName;

    /**
     * @var string
     */
    private $body;

    /**
     * @param string             $id           Id
     * @param string             $typeName     Type name
     * @param \DateTimeImmutable $anOccurredOn Occurred on
     * @param string             $anEventBody  Event body
     * @throws \Exception
     */
    public function __construct(
        string $id,
        string $typeName,
        \DateTimeImmutable $anOccurredOn,
        string $anEventBody
    ) {
        $this->id         = $id;
        $this->body       = $anEventBody;
        $this->typeName   = $typeName;
        $this->occurredOn = $anOccurredOn;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    /**
     * @return string
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * @return string
     */
    public function typeName(): string
    {
        return $this->typeName;
    }
}
