<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use ProBillerNG\BI\Event\BaseEvent;

class TestTransactionVoided extends BaseEvent
{
    const TYPE = 'Void_Test_CreditCard_Transaction';

    const LATEST_VERSION = 1;

    /**
     * @var int
     */
    private $testEvent;

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
    private $ccCardFirst6;

    /**
     * @var string
     */
    private $ccCardLast4;

    /**
     * @var string
     */
    private $ccExpiration;

    /**
     * @var int
     */
    private $voidedSuccessfully;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * TestTransactionVoided constructor.
     *
     * @param string $transactionId      Transaction id
     * @param int    $testEvent          Is test event
     * @param float  $amount             Amount
     * @param string $ccCardFirst6       CC first 6
     * @param string $ccCardLast4        CC last 4
     * @param string $ccExpiration       Expiration date
     * @param int    $voidedSuccessfully Is successfully voided
     * @throws \Exception
     */
    public function __construct(
        string $transactionId,
        int $testEvent,
        float $amount,
        string $ccCardFirst6,
        string $ccCardLast4,
        string $ccExpiration,
        int $voidedSuccessfully
    ) {
        parent::__construct(self::TYPE);

        $this->timestamp          = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->transactionId      = $transactionId;
        $this->testEvent          = $testEvent;
        $this->amount             = $amount;
        $this->ccCardFirst6       = $ccCardFirst6;
        $this->ccCardLast4        = $ccCardLast4;
        $this->ccExpiration       = $ccExpiration;
        $this->voidedSuccessfully = $voidedSuccessfully;

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'type'               => self::TYPE,
            'version'            => self::LATEST_VERSION,
            'timestamp'          => $this->timestamp,
            'transactionId'      => $this->transactionId,
            'testEvent'          => $this->testEvent,
            'amount'             => $this->amount,
            'ccCardFirst6'       => $this->ccCardFirst6,
            'ccCardLast4'        => $this->ccCardLast4,
            'ccExpiration'       => $this->ccExpiration,
            'voidedSuccessfully' => $this->voidedSuccessfully,
        ];
    }
}
