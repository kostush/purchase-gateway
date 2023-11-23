<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use Ramsey\Uuid\Uuid;

/**
 * Class IdString is meant to support any string.
 * The need was because TrafficJunky have to provide their member ids, but not in UUID format.
 *
 * @package ProBillerNG\Domain
 */
abstract class IdString
{
    /**
     * @var string
     */
    protected $value;

    /**
     * IdString constructor.
     *
     * @param string|null $value Value
     *
     * @throws \Exception
     */
    private function __construct(string $value = null)
    {
        $this->value = $value ?: Uuid::uuid4()->toString();
    }

    /**
     * Create new IdString from given value
     *
     * @param mixed|null $value Value
     *
     * @return IdString
     * @throws \Exception
     */
    public static function create($value = null): self
    {
        return new static((string) $value);
    }

    /**
     * Create new IdString from string
     *
     * @param string $value Value
     *
     * @return IdString
     * @throws \Exception
     */
    public static function createFromString(string $value): self
    {
        return new static($value);
    }

    /**
     * @return string
     */
    public function value(): string
    {
        return $this->value;
    }

    /**
     * String representation of IdString
     * Required for Doctrine persistence
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->value();
    }

    /**
     * Compares two Ids
     *
     * @param IdString $new IdString
     *
     * @return bool
     */
    public function equals(IdString $new): bool
    {
        return $this->value() == $new->value();
    }
}
