<?php
declare(strict_types=1);


namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

class RocketgateBillerTransaction implements BillerTransaction
{
    /**
     * @var string
     */
    private $invoiceId;

    /**
     * @var string
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
     * BillerTransaction constructor.
     * @param string $invoiceId           The rocketgate invoice id
     * @param string $customerId          The rocketgate customer id
     * @param string $billerTransactionId The rocketgate biller transaction id
     * @param string $type                The rocketgate transaction type
     */
    private function __construct(
        string $invoiceId,
        string $customerId,
        string $billerTransactionId,
        string $type
    ) {
        $this->invoiceId           = $invoiceId;
        $this->customerId          = $customerId;
        $this->billerTransactionId = $billerTransactionId;
        $this->type                = $type;
    }

    /**
     * @param string $invoiceId           The rocketgate invoice id
     * @param string $customerId          The rocketgate customer id
     * @param string $billerTransactionId The rocketgate biller transaction id
     * @param string $type                The rocketgate transaction type
     * @return self
     */
    public static function create(
        string $invoiceId,
        string $customerId,
        string $billerTransactionId,
        string $type
    ): self {
        return new static($invoiceId, $customerId, $billerTransactionId, $type);
    }

    /**
     * @return string
     */
    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    /**
     * @return string
     */
    public function getCustomerId(): string
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
}
