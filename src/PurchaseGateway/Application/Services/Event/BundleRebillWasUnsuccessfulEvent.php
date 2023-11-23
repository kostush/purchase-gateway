<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Event;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction\Response\TransactionInformation;

class BundleRebillWasUnsuccessfulEvent extends BaseEvent
{
    /**
     * Integration event name
     * @var string
     */
    public const INTEGRATION_NAME = 'ProbillerNG\\Events\\BundleRebillWasUnsuccessful';

    /** @var string */
    protected $sessionId;

    /** @var string */
    protected $memberId;

    /** @var string */
    protected $itemId;

    /**  @var string */
    protected $bundleId;

    /** @var string */
    protected $gracePeriodEndDate;

    /**
     * BundleRebillWasUnsuccessfulEvent constructor.
     *
     * @param string $sessionId          Session Id.
     * @param string $memberId           Member Id.
     * @param string $itemId             Item Id.
     * @param string $bundleId           Bundle Id.
     * @param string $gracePeriodEndDate Grace period end date.
     * @throws \Exception
     */
    public function __construct(
        string $sessionId,
        string $memberId,
        string $itemId,
        string $bundleId,
        string $gracePeriodEndDate
    ) {
        parent::__construct($itemId, new \DateTimeImmutable());

        $this->sessionId          = $sessionId;
        $this->memberId           = $memberId;
        $this->itemId             = $itemId;
        $this->bundleId           = $bundleId;
        $this->gracePeriodEndDate = $gracePeriodEndDate;
    }

    /**
     * @param PurchaseProcessed      $purchaseProcessed      Purchase Processed.
     * @param TransactionInformation $transactionInformation Transaction information
     * @return BundleRebillWasUnsuccessfulEvent
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

        $gracePeriodEndDate = $transactionInformation->createdAt();
        $gracePeriodEndDate = $gracePeriodEndDate->add(new \DateInterval('P3D'));

        return new self(
            $purchaseProcessed->sessionId(),
            $purchaseProcessed->memberId(),
            $itemId,
            $purchaseProcessed->bundleId(),
            $gracePeriodEndDate->format('Y-m-d\TH:i:sO')
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
    public function gracePeriodEndDate(): string
    {
        return $this->gracePeriodEndDate;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'               => self::INTEGRATION_NAME,
            'sessionId'          => $this->sessionId(),
            'memberId'           => $this->memberId(),
            'itemId'             => $this->itemId(),
            'bundleId'           => $this->bundleId(),
            'gracePeriodEndDate' => $this->gracePeriodEndDate(),
            'occurredOn'         => $this->occurredOn(),
        ];
    }
}
