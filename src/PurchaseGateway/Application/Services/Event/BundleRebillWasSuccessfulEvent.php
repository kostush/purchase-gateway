<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class BundleRebillWasSuccessfulEvent extends BaseEvent
{
    /**
     * Integration event name
     * @var string
     */
    public const INTEGRATION_NAME = 'ProbillerNG\\Events\\BundleRebillWasSuccessful';


    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $memberId;

    /** @var string */
    protected $itemId;

    /**  @var string */
    protected $bundleId;

    /** @var string */
    protected $rebillDate;

    /** @var bool */
    protected $withCharge;

    /**
     * BundleRebillWasSuccessfulEvent constructor.
     *
     * @param string $sessionId  Session Id.
     * @param string $memberId   Member Id.
     * @param string $itemId     Item Id.
     * @param string $bundleId   Bundle Id.
     * @param string $rebillDate Rebill Date.
     * @throws \Exception
     */
    public function __construct(
        string $sessionId,
        string $memberId,
        string $itemId,
        string $bundleId,
        string $rebillDate
    ) {
        parent::__construct($itemId, new \DateTimeImmutable());

        $this->sessionId  = $sessionId;
        $this->memberId   = $memberId;
        $this->itemId     = $itemId;
        $this->bundleId   = $bundleId;
        $this->rebillDate = $rebillDate;
        $this->withCharge = true;
    }

    /**
     * @param PurchaseProcessed      $purchaseProcessed      Purchase Processed.
     * @param TransactionInformation $transactionInformation Transaction information
     * @return BundleRebillWasSuccessfulEvent
     * @throws \Exception
     */
    public static function createFromPurchase(
        PurchaseProcessed $purchaseProcessed,
        TransactionInformation $transactionInformation
    ): self {

        try {
            $itemId = $purchaseProcessed->itemId();
        } catch (\Throwable $exception) {
            $itemId = $purchaseProcessed->lastTransactionId();
        }

        $nextRebillDate = $transactionInformation->createdAt();
        $nextRebillDate = $nextRebillDate->add(new \DateInterval('P' . $purchaseProcessed->rebillFrequency() . 'D'));

        return new self(
            $purchaseProcessed->sessionId(),
            $purchaseProcessed->memberId(),
            $itemId,
            $purchaseProcessed->bundleId(),
            $nextRebillDate->format('Y-m-d\TH:i:sO')
        );
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function memberId(): string
    {
        return $this->memberId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function itemId(): string
    {
        return $this->itemId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function bundleId(): string
    {
        return $this->bundleId;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function rebillDate(): string
    {
        return $this->rebillDate;
    }

    /**
     * @return bool
     * @codeCoverageIgnore
     */
    public function withCharge(): bool
    {
        return $this->withCharge;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'       => self::INTEGRATION_NAME,
            'sessionId'  => $this->sessionId(),
            'memberId'   => $this->memberId(),
            'itemId'     => $this->itemId(),
            'bundleId'   => $this->bundleId(),
            'rebillDate' => $this->rebillDate(),
            'withCharge' => $this->withCharge(),
            'occurredOn' => $this->occurredOn(),
        ];
    }
}
