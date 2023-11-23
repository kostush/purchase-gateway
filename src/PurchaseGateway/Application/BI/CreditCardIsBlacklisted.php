<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\BI;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use ProBillerNG\BI\Event\BaseEvent;

class CreditCardIsBlacklisted extends BaseEvent implements Arrayable
{
    public const CARD_BLACKLISTED = 'cardblacklisted';
    public const TYPE             = 'Blacklist_data';
    public const LATEST_VERSION   = 1;

    /**
     * @var string
     */
    private $timestamp;

    /**
     * @var string
     */
    private $firstSix;

    /**
     * @var string
     */
    private $lastFour;

    /**
     * @var string
     */
    private $expirationMonth;

    /**
     * @var string
     */
    private $expirationYear;

    /**
     * @var string|null
     */
    private $memberId;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $amount;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * CreditCardIsBlacklisted constructor.
     * @param string      $firstSix        CC first six.
     * @param string      $lastFour        CC last four.
     * @param string      $expirationMonth CC expiration month.
     * @param string      $expirationYear  CC expiration year.
     * @param string      $email           Email.
     * @param string      $amount          Amount.
     * @param string      $sessionId       Session id.
     * @param string|null $memberId        Member id.
     * @throws Exception
     */
    public function __construct(
        string $firstSix,
        string $lastFour,
        string $expirationMonth,
        string $expirationYear,
        string $email,
        string $amount,
        string $sessionId,
        ?string $memberId
    ) {
        parent::__construct(static::TYPE);

        $this->timestamp       = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->firstSix        = $firstSix;
        $this->lastFour        = $lastFour;
        $this->expirationMonth = $expirationMonth;
        $this->expirationYear  = $expirationYear;
        $this->email           = $email;
        $this->amount          = $amount;
        $this->sessionId       = $sessionId;
        $this->memberId        = $memberId;

        $this->setValue($this->toArray());
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'version'             => static::LATEST_VERSION,
            'eventIssueTimestamp' => $this->timestamp,
            'sessionId'           => $this->sessionId,
            'correlationId'       => $this->sessionId,
            'eventName'           => static::TYPE,
            'memberId'            => $this->memberId,
            'email'               => $this->email,
            'amount'              => $this->amount,
            'blacklistedInfo'     => [
                'reason' => self::CARD_BLACKLISTED
            ],
            'paymentInformation'  => [
                'first6'     => $this->firstSix,
                'last4'      => $this->lastFour,
                'expiryDate' => $this->expirationMonth . '/' . $this->expirationYear
            ]
        ];
    }
}
