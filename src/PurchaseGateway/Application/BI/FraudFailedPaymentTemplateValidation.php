<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Carbon\Carbon;
use ProBillerNG\BI\Event\BaseEvent;

class FraudFailedPaymentTemplateValidation extends BaseEvent
{
    const TYPE = 'Fraud_Failed_Payment_Template_Validation';

    const LATEST_VERSION = 1;

    const TEST_EVENT = false;

    /** E-mail. Ex.: "user@mail.com" */
    private $memberEmail;

    /** Timestamp. Ex.: "2021-03-12T17:47:01.836904Z" */
    private $eventIssueTimeStamp;

    /** Timestamp. Ex.: "2021-03-12T17:47:01.836904Z" */
    private $timestamp;

    /** @var string Site Id */
    private $siteId;

    /** @var string Business Group Id */
    private $businessGroupId;

    /**
     * FraudFailedPaymentTemplateValidation constructor.
     * This is an event created for when we have invalid last4
     *
     * @param string $memberEmail
     * @param string $timestamp
     * @param string $siteId
     * @param string $businessGroupId
     */
    public function __construct(
        string $memberEmail,
        string $timestamp,
        string $siteId,
        string $businessGroupId
    ) {
        parent::__construct(self::TYPE);

        $this->memberEmail         = $memberEmail;
        $this->eventIssueTimeStamp = Carbon::now()->toISOString();
        $this->siteId              = $siteId;
        $this->timestamp           = $timestamp;
        $this->businessGroupId     = $businessGroupId;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "memberInfo"      => [
                "email" => $this->memberEmail
            ],
            "type"            => self::TYPE,
            "version"         => self::LATEST_VERSION,
            "timestamp"       => $this->timestamp,
            "siteId"          => $this->siteId,
            "businessGroupId" => $this->businessGroupId,
        ];
    }

}
