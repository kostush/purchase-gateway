<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Event\BaseEvent;
use ProBillerNG\Base\Domain\AggregateRoot;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidTransactionStateException;

class Purchase extends AggregateRoot
{
    /** @var string */
    public const STATUS_SUCCESS = 'success';

    /** @var string */
    public const STATUS_FAILED = 'failed';

    /** @var string */
    public const STATUS_PENDING = 'pending';

    /** @var array */
    private $statusMap = [
        Transaction::STATUS_ABORTED  => self::STATUS_FAILED,
        Transaction::STATUS_DECLINED => self::STATUS_FAILED,
        Transaction::STATUS_APPROVED => self::STATUS_SUCCESS,
        Transaction::STATUS_PENDING  => self::STATUS_PENDING
    ];

    /**
     * @var PurchaseId
     */
    private $purchaseId;

    /**
     * @var MemberId
     */
    private $memberId;

    /**
     * @var SessionId
     */
    private $sessionId;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @var ProcessedItemsCollection
     */
    private $items;

    /**
     * @var string
     */
    private $status;

    /**
     * Purchase constructor.
     * @param PurchaseId               $purchaseId Purchase Id
     * @param MemberId                 $memberId   Member Id
     * @param SessionId                $sessionId  Session Id
     * @param \DateTimeImmutable       $createdAt  Created at
     * @param ProcessedItemsCollection $items      Collection of items
     * @param string|null              $status     The state of the purchase
     * @throws InvalidTransactionStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(
        PurchaseId $purchaseId,
        MemberId $memberId,
        SessionId $sessionId,
        \DateTimeImmutable $createdAt,
        ProcessedItemsCollection $items,
        ?string $status
    ) {
        $this->purchaseId = $purchaseId;
        $this->memberId   = $memberId;
        $this->sessionId  = $sessionId;
        $this->createdAt  = $createdAt;
        $this->items      = $items;

        $this->initStatus($status);
    }

    /**
     * @param PurchaseId                    $purchaseId     Purchase Id
     * @param MemberId|null                 $memberId       Member Id
     * @param SessionId                     $sessionId      Session Id
     * @param ProcessedItemsCollection|null $itemCollection Collection of items
     * @param string|null                   $status         The state of the purchase
     * @return Purchase
     * @throws \Exception
     */
    public static function create(
        PurchaseId $purchaseId,
        ?MemberId $memberId,
        SessionId $sessionId,
        ?ProcessedItemsCollection $itemCollection,
        ?string $status
    ): self {
        return new static(
            $purchaseId,
            $memberId,
            $sessionId,
            new \DateTimeImmutable(),
            $itemCollection ?: new ProcessedItemsCollection(),
            $status
        );
    }

    /**
     * @param BaseEvent $purchaseProcessed BaseEvent
     * @throws \Exception
     * @return void
     */
    public function sendProcessedEvent(BaseEvent $purchaseProcessed): void
    {
        // Create purchase processed domain event
        $this->addEvent($purchaseProcessed);
    }

    /**
     * @param ProcessedBundleItem $item ProcessedBundleItem
     * @return void
     */
    public function addItem(ProcessedBundleItem $item): void
    {
        $this->items->offsetSet((string) $item->itemId(), $item);
    }

    /**
     * @return PurchaseId
     */
    public function purchaseId(): PurchaseId
    {
        return $this->purchaseId;
    }

    /**
     * @return MemberId|null
     */
    public function memberId(): ?MemberId
    {
        return $this->memberId;
    }

    /**
     * @return SessionId
     */
    public function sessionId(): SessionId
    {
        return $this->sessionId;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return ProcessedItemsCollection
     */
    public function items()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return 'purchase';
    }

    /**
     * @return string
     */
    public function getEntityId(): string
    {
        return (string) $this->purchaseId();
    }

    /**
     * @return string
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * @return ProcessedItem
     */
    public function retrieveMainPurchaseItem(): ProcessedItem
    {
        foreach ($this->items() as $item) {
            if (!$item->isCrossSale()) {
                return $item;
            }
        }
    }

    /**
     * @param string|null $transactionState Transaction State
     * @return void
     * @throws InvalidTransactionStateException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initStatus(?string $transactionState): void
    {
        if ($transactionState === null) {
            $this->status = self::STATUS_FAILED;
            return;
        }

        if (empty($this->statusMap[$transactionState])) {
            throw new InvalidTransactionStateException($transactionState ?? 'null given');
        }

        $this->status = $this->statusMap[$transactionState];
    }
}
