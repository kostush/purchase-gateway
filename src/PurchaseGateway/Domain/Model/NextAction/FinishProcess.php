<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\NextAction;

class FinishProcess extends NextAction
{
    public const TYPE = 'finishProcess';

    /**
     * @var null|string
     */
    protected $resolution;

    /**
     * @var null|string
     */
    protected $reason;

    /**
     * FinishProcess constructor.
     * @param string|null $resolution
     * @param string|null $reason
     */
    public function __construct(?string $resolution, ?string $reason)
    {
        $this->resolution = $resolution;
        $this->reason     = $reason;
    }

    /**
     * @return FinishProcess
     */
    public static function create(?string $resolution = null, ?string $reason = null): self
    {
        return new static($resolution, $reason);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $payload = ['type' => $this->type(), 'resolution' => $this->resolution, 'reason' => $this->reason];

        return array_filter($payload);
    }
}
