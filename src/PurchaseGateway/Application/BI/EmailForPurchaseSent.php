<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\PurchaseGateway\Application\BI\Processed\Member;
use ProBillerNG\PurchaseGateway\Domain\Model\Event\PurchaseProcessed;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;

class EmailForPurchaseSent extends PurchaseEvent
{
    const TYPE = 'Email_For_Purchase_Sent';

    const LATEST_VERSION = 1;

    /** @var Processed\Member */
    protected $member;

    /** @var string */
    protected $memberId;

    /** @var string */
    protected $purchaseId;

    /** @var string */
    protected $receiptId;

    /** @var string */
    protected $subscriptionId;

    /**
     * EmailForPurchaseSent constructor.
     * @param Processed\Member $member         Member Info
     * @param string           $memberId       Member Id
     * @param string           $sessionId      Session Id
     * @param string           $siteId         Site Id
     * @param string           $purchaseId     Purchase Id
     * @param string           $receiptId      Receipt Id
     * @param string|null      $subscriptionId Subscription Id
     * @throws \Exception
     *
     */
    public function __construct(
        Processed\Member $member,
        string $memberId,
        string $sessionId,
        string $siteId,
        string $purchaseId,
        string $receiptId,
        ?string $subscriptionId
    ) {
        parent::__construct(self::TYPE, $sessionId, $siteId, new \DateTimeImmutable());

        $this->member         = $member;
        $this->memberId       = $memberId;
        $this->purchaseId     = $purchaseId;
        $this->receiptId      = $receiptId;
        $this->subscriptionId = $subscriptionId;

        $this->setValue($this->toArray());
    }

    /**
     * @param MemberInfo        $memberInfo             MemberInfo
     * @param PurchaseProcessed $purchaseProcessedEvent PurchaseProcessedEvent
     * @param string            $traceId                TraceId
     * @return EmailForPurchaseSent
     * @throws \Exception
     */
    public static function createFromEventAndTraceId(
        MemberInfo $memberInfo,
        PurchaseProcessed $purchaseProcessedEvent,
        string $traceId
    ): self {
        return new self(
            Member::create(
                $purchaseProcessedEvent->memberInfo()['email'] ?? (string) $memberInfo->email(),
                $purchaseProcessedEvent->memberInfo()['username'] ?? (string) $memberInfo->username(),
                $purchaseProcessedEvent->memberInfo()['firstName'] ?? (string) $memberInfo->firstName(),
                $purchaseProcessedEvent->memberInfo()['lastName'] ?? (string) $memberInfo->lastName(),
                $purchaseProcessedEvent->memberInfo()['country'] ?? null,
                $purchaseProcessedEvent->memberInfo()['zipCode'] ?? null,
                $purchaseProcessedEvent->memberInfo()['address'] ?? null,
                $purchaseProcessedEvent->memberInfo()['city'] ?? null,
                $purchaseProcessedEvent->memberInfo()['phoneNumber'] ?? null
            ),
            (string) $purchaseProcessedEvent->memberId(),
            (string) $purchaseProcessedEvent->sessionId(),
            (string) $purchaseProcessedEvent->siteId(),
            (string) $purchaseProcessedEvent->purchaseId(),
            (string) $traceId,
            (string) $purchaseProcessedEvent->subscriptionId()
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'type'           => self::TYPE,
            'version'        => $this->version,
            'timestamp'      => $this->timestamp,
            'sessionId'      => $this->sessionId,
            'memberInfo'     => $this->member->toArray(),
            'memberId'       => $this->memberId,
            'subscriptionId' => $this->subscriptionId,
            'purchaseId'     => $this->purchaseId,
            'receiptId'      => $this->receiptId,
        ];
    }
}
