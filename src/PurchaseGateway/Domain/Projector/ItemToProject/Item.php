<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\Projection\Domain\ItemToProject;

abstract class Item implements ItemToProject
{
    /** @var int */
    protected $id;

    /** @var \DateTimeImmutable */
    protected $occurredOn;

    /** @var string */
    protected $body;

    /**
     * AddonCreated constructor.
     * @param int                $id         Aggregate id
     * @param \DateTimeImmutable $occurredOn Occured on
     * @param string             $body       Body
     */
    public function __construct(
        int $id,
        \DateTimeImmutable $occurredOn,
        string $body
    ) {
        $this->id         = $id;
        $this->occurredOn = $occurredOn;
        $this->body       = $body;
    }

    /**
     * @return int
     */
    public function id(): int
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
    abstract public function typeName(): string;

    /**
     * @return string
     */
    abstract public function originalEventName(): string;
}