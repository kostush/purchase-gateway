<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

class QyssoBillerTransaction implements BillerTransaction
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $billerTransactionId;

    /**
     * @var array
     */
    private $rawBillerResponse;

    /**
     * @var string|null
     */
    private $initialBillerTransactionId;

    /**
     * BillerTransaction constructor.
     * @param string      $type                       Transaction type
     * @param string      $billerTransactionId        The epoch  transaction id
     * @param array       $rawBillerResponse          Raw biller response
     * @param string|null $initialBillerTransactionId Initial biller transaction id
     */
    private function __construct(
        string $type,
        string $billerTransactionId,
        array $rawBillerResponse,
        ?string $initialBillerTransactionId
    ) {
        $this->type                       = $type;
        $this->billerTransactionId        = $billerTransactionId;
        $this->rawBillerResponse          = $rawBillerResponse;
        $this->initialBillerTransactionId = $initialBillerTransactionId;
    }

    /**
     * @param string      $type                       Transaction type
     * @param string      $billerTransactionId        The epoch  transaction id
     * @param array       $rawBillerResponse          Raw biller response
     * @param string|null $initialBillerTransactionId Initial biller transaction id
     * @return self
     */
    public static function create(
        string $type,
        string $billerTransactionId,
        array $rawBillerResponse,
        ?string $initialBillerTransactionId
    ): self {
        return new static($type, $billerTransactionId, $rawBillerResponse, $initialBillerTransactionId);
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function billerTransactionId(): string
    {
        return $this->billerTransactionId;
    }

    /**
     * @return array
     */
    public function rawBillerResponse(): array
    {
        return $this->rawBillerResponse;
    }

    /**
     * @return string|null
     */
    public function initialBillerTransactionId(): ?string
    {
        return $this->initialBillerTransactionId;
    }
}
