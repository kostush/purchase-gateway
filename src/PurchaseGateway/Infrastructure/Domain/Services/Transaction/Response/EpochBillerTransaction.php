<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

class EpochBillerTransaction implements BillerTransaction
{
    /**
     * @var string
     */
    private $piCode;

    /**
     * @var string
     */
    private $billerMemberId;

    /**
     * @var string
     */
    private $billerTransactionId;

    /**
     * @var string
     */
    private $ans;

    /**
     * BillerTransaction constructor.
     * @param string $piCode              The epoch product id
     * @param string $billerMemberId      The epoch member id
     * @param string $billerTransactionId The epoch  transaction id
     * @param string $ans                 The epoch ans
     */
    private function __construct(
        string $piCode,
        string $billerMemberId,
        string $billerTransactionId,
        string $ans
    ) {
        $this->piCode              = $piCode;
        $this->billerMemberId      = $billerMemberId;
        $this->billerTransactionId = $billerTransactionId;
        $this->ans                 = $ans;
    }

    /**
     * @param string $piCode              The epoch product id
     * @param string $billerMemberId      The epoch member id
     * @param string $billerTransactionId The epoch  transaction id
     * @param string $ans                 The epoch ans
     * @return self
     */
    public static function create(
        string $piCode,
        string $billerMemberId,
        string $billerTransactionId,
        string $ans
    ): self {
        return new static($piCode, $billerMemberId, $billerTransactionId, $ans);
    }

    /**
     * @return string
     */
    public function piCode(): string
    {
        return $this->piCode;
    }

    /**
     * @return string
     */
    public function billerMemberId(): string
    {
        return $this->billerMemberId;
    }

    /**
     * @return string
     */
    public function billerTransactionId(): string
    {
        return $this->billerTransactionId;
    }

    /**
     * @return string
     */
    public function ans(): string
    {
        return $this->ans;
    }
}
