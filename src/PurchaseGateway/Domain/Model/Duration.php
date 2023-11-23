<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class Duration
{
    /**
     * @var int
     */
    private $days;

    /**
     * Duration constructor.
     * @param int $days Number of days
     */
    private function __construct(int $days)
    {
        $this->days = $days;
    }

    /**
     * @param int $days Number of days
     * @return Duration
     */
    public static function create(int $days): self
    {
        return new static($days);
    }

    /**
     * @return int
     */
    public function days(): int
    {
        return $this->days;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->days;
    }
}
