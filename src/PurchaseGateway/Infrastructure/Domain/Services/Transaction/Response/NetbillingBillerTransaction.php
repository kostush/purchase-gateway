<?php
declare(strict_types=1);


namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

class NetbillingBillerTransaction implements BillerTransaction
{
    /**
     * @var null|string
     */
    private $customerId;

    /**
     * @var string
     */
    private $billerTransactionId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var bool
     */
    private $status;

    /**
     * BillerTransaction constructor.
     * @param null|string $customerId          The netbilling customer id
     * @param string      $billerTransactionId The netbilling biller transaction id
     * @param string      $type                The netbilling transaction type
     * @param bool        $status              Status
     */
    private function __construct(
        ?string $customerId,
        string $billerTransactionId,
        string $type,
        bool $status
    ) {
        $this->customerId          = $customerId;
        $this->billerTransactionId = $billerTransactionId;
        $this->type                = $type;
        $this->status              = $status;
    }

    /**
     * @param null|string $customerId          The netbilling customer id
     * @param string      $billerTransactionId The netbilling biller transaction id
     * @param string      $type                The netbilling transaction type
     * @param bool        $status              Status
     * @return self
     */
    public static function create(
        ?string $customerId,
        string $billerTransactionId,
        string $type,
        bool $status
    ): self {
        return new static($customerId, $billerTransactionId, $type, $status);
    }

    /**
     * @return null|string
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @return string
     */
    public function getBillerTransactionId(): string
    {
        return $this->billerTransactionId;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function getStatus(): bool
    {
        return $this->status;
    }
}
