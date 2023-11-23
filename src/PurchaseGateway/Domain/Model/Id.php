<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use Ramsey\Uuid\Uuid;

/**
 * Class Id
 * Generic ID class
 *
 * @package ProBillerNG\Domain
 */
abstract class Id
{
    /**
     * @var Uuid
     */
    protected $value;

    /**
     * Id constructor.
     *
     * @param Uuid $value Uuid
     * @throws \Exception
     */
    private function __construct(Uuid $value = null)
    {
        $this->value = $value ?: Uuid::uuid4();
    }

    /**
     * Create new Id from Uuid
     *
     * @param Uuid|null $value Uuid
     * @return Id
     * @throws \Exception
     */
    public static function create(Uuid $value = null): self
    {
        return new static($value);
    }

    /**
     * Create new Id from string
     *
     * @param string $value Value
     * @return Id
     * @throws \InvalidArgumentException
     */
    public static function createFromString(string $value): self
    {
        try {
            return new static(Uuid::fromString($value));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(
                'Invalid ' . strtolower(get_called_class()) . ' id'
            );
        }
    }

    /**
     * @return Uuid
     */
    public function value(): Uuid
    {
        return $this->value;
    }

    /**
     * String representation of Id
     * Required for Doctrine persistence
     *
     * @return string
     */
    public function __toString()
    {
        return $this->value()->toString();
    }

    /**
     * Compares two Ids
     *
     * @param Id $new Id
     * @return bool
     */
    public function equals(Id $new): bool
    {
        return $this->value()->toString() == $new->value()->toString();
    }
}
