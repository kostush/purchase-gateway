<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class BinRouting
{
    public const MAX_ATTEMPTS = 2;

    /**
     * @var int
     */
    private $attempt;

    /**
     * @var string|null
     */
    private $routingCode;

    /**
     * @var string|null
     */
    private $bankName;

    /**
     * BinRouting constructor.
     *
     * @param int         $attempt     The atempt number
     * @param string|null $routingCode The routing code
     * @param string|null $bankName    The bank name
     */
    private function __construct(
        int $attempt,
        string $routingCode = null,
        string $bankName = null
    ) {
        $this->attempt     = $attempt;
        $this->routingCode = $routingCode;
        $this->bankName    = $bankName;
    }

    /**
     * @param int         $attempt     The atempt number
     * @param string|null $routingCode The routing code
     * @param string|null $bankName    The bank name
     *
     * @return BinRouting
     */
    public static function create(int $attempt, string $routingCode = null, string $bankName = null): self
    {
        return new static($attempt, $routingCode, $bankName);
    }

    /**
     * @return int
     */
    public function attempt(): int
    {
        return $this->attempt;
    }

    /**
     * @return string|null
     */
    public function routingCode(): ?string
    {
        return $this->routingCode;
    }

    /**
     * @return string|null
     */
    public function bankName(): ?string
    {
        return $this->bankName;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'attempt'     => $this->attempt(),
            'routingCode' => $this->routingCode(),
            'bankName'    => $this->bankName()
        ];
    }
}
