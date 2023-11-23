<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response;

use DateTimeImmutable;
use ProBillerNG\Logger\Exception;
use ProBillerNG\PurchaseGateway\Domain\Services\BillerFactoryService;
use ProBillerNG\PurchaseGateway\Domain\Services\Exception\UnknownBillerIdException;

abstract class TransactionInformation
{
    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $status;

    /**
     * @var DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var float|null
     */
    private $rebillAmount;

    /**
     * @var int|null
     */
    private $rebillFrequency;

    /**
     * @var int|null
     */
    private $rebillStart;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var bool
     */
    private $isNsf;

    /**
     * TransactionInformation constructor.
     * @param string            $transactionId   Transaction UUID
     * @param float             $amount          Amount
     * @param string            $status          Transaction status
     * @param DateTimeImmutable $createdAt       Created at
     * @param float|null        $rebillAmount    Rebill amount
     * @param int|null          $rebillFrequency Rebill frequency
     * @param int|null          $rebillStart     Rebill start
     * @param string            $billerId        Biller id
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    public function __construct(
        string $transactionId,
        float $amount,
        string $status,
        DateTimeImmutable $createdAt,
        ?float $rebillAmount,
        ?int $rebillFrequency,
        ?int $rebillStart,
        string $billerId
    ) {
        $this->transactionId   = $transactionId;
        $this->amount          = $amount;
        $this->status          = $status;
        $this->createdAt       = $createdAt;
        $this->rebillAmount    = $rebillAmount;
        $this->rebillFrequency = $rebillFrequency;
        $this->rebillStart     = $rebillStart;
        $this->isNsf           = false;
        $this->initBillerName($billerId);
    }

    /**
     * @return string
     */
    public function transactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @return float
     */
    public function amount(): float
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return DateTimeImmutable
     */
    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return float
     */
    public function rebillAmount(): ?float
    {
        return $this->rebillAmount;
    }

    /**
     * @return int
     */
    public function rebillFrequency(): ?int
    {
        return $this->rebillFrequency;
    }

    /**
     * @return int
     */
    public function rebillStart(): ?int
    {
        return $this->rebillStart;
    }

    /**
     * @return bool
     */
    public function isNsf(): bool
    {
        return $this->isNsf;
    }

    /**
     * @param bool $isNsf Is NSF
     * @return void
     */
    public function setIsNsf(bool $isNsf): void
    {
        $this->isNsf = $isNsf;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }

    /**
     * @param string $billerId The biller id
     * @return void
     * @throws Exception
     * @throws UnknownBillerIdException
     */
    private function initBillerName(string $billerId): void
    {
        $biller = BillerFactoryService::createFromBillerId($billerId);

        $this->billerName = $biller->name();
    }
}
