<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain;

class FailedEventPublish
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $aggregateId;

    /**
     * @var bool
     */
    protected $published;

    /**
     * @var int
     */
    protected $retries;

    /**
     * @var \DateTimeImmutable
     */
    protected $lastAttempted;

    /**
     * @var \DateTimeImmutable
     */
    protected $timestamp;

    /**
     * FailedEventPublish constructor.
     * @param string             $aggregateId   Event id
     * @param bool               $published     Published flag
     * @param int                $retries       Retries
     * @param \DateTimeImmutable $lastAttempted Last retry attempt date
     * @param \DateTimeImmutable $timestamp     Failure date
     */
    private function __construct(
        string $aggregateId,
        bool $published,
        int $retries,
        \DateTimeImmutable $lastAttempted,
        \DateTimeImmutable $timestamp
    ) {
        $this->aggregateId   = $aggregateId;
        $this->published     = $published;
        $this->retries       = $retries;
        $this->lastAttempted = $lastAttempted;
        $this->timestamp     = $timestamp;
    }

    /**
     * @param string $aggregateId Event id
     *
     * @return self
     *
     * @throws \Exception
     */
    public static function create(
        string $aggregateId
    ): self {
        return new self(
            $aggregateId,
            false,
            0,
            new \DateTimeImmutable(),
            new \DateTimeImmutable()
        );
    }

    /**
     * @return int
     * @codeCoverageIgnore
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function published(): bool
    {
        return $this->published;
    }

    /**
     * @return int
     * @codeCoverageIgnore
     */
    public function retries(): int
    {
        return $this->retries;
    }

    /**
     * @return \DateTimeImmutable
     * @codeCoverageIgnore
     */
    public function lastAttempted(): \DateTimeImmutable
    {
        return $this->lastAttempted;
    }

    /**
     * @return self
     * @throws \Exception
     */
    public function increaseRetryCount(): self
    {
        $this->retries++;
        $this->lastAttempted = new \DateTimeImmutable();
        return $this;
    }

    /**
     * @return FailedEventPublish
     */
    public function markPublished(): self
    {
        $this->published = true;
        return $this;
    }
}
