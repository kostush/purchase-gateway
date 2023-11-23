<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class AddonType
{
    public const CONTENT = 'content';
    public const FEATURE = 'feature';

    /**
     * @var string
     */
    private $type;

    /**
     * AddonType constructor.
     * @param string $type Type
     */
    private function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * @param string $type Type
     *
     * @return AddonType
     */
    public static function create(string $type): self
    {
        return new static($type);
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function __toString()
    {
        return $this->type();
    }
}
