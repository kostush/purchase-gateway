<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use ProBillerNG\PurchaseGateway\Domain\Model\TransactionId;

class QyssoBillerInteraction implements BillerInteraction
{
    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $paymentType;

    /**
     * @var string
     */
    private $paymentMethod;

    /**
     * QyssoBillerInteraction constructor.
     * @param string      $transactionId Transaction id
     * @param string      $status        Status
     * @param string      $paymentType   Payment type
     * @param string|null $paymentMethod Payment method
     */
    private function __construct(
        string $transactionId,
        string $status,
        string $paymentType,
        ?string $paymentMethod
    ) {
        $this->transactionId = $transactionId;
        $this->status        = $status;
        $this->paymentType   = $paymentType;
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * @param TransactionId $transactionId Transaction id
     * @param string        $status        Transaction status
     * @param string        $paymentType   Payment type
     * @param null|string   $paymentMethod Payment method
     * @return QyssoBillerInteraction
     */
    public static function create(
        TransactionId $transactionId,
        string $status,
        string $paymentType,
        ?string $paymentMethod
    ): self {
        return new static(
            $transactionId,
            $status,
            $paymentType,
            $paymentMethod
        );
    }

    /**
     * @return string
     */
    public function transactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function paymentType(): string
    {
        return $this->paymentType;
    }

    /**
     * @return string
     */
    public function paymentMethod(): string
    {
        return $this->paymentMethod;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'transactionId' => $this->transactionId(),
            'status'        => $this->status(),
            'paymentType'   => $this->paymentType(),
            'paymentMethod' => $this->paymentMethod(),
        ];
    }
}
