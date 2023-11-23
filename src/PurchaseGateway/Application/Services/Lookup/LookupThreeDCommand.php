<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Application\Services\Lookup;

use ProBillerNG\Base\Application\Services\Command;
use ProBillerNG\PurchaseGateway\Domain\Model\Site;

class LookupThreeDCommand extends Command
{
    /**
     * @var Site
     */
    private $site;

    /**
     * @var string
     */
    private $ccNum;

    /**
     * @var string
     */
    private $cvv;

    /**
     * @var string
     */
    private $expirationMonth;

    /**
     * @var string
     */
    private $expirationYear;

    /**
     * @var string
     */
    private $sessionId;

    /**
     * @var string
     */
    private $deviceFingerprintId;

    /**
     * LookupThreeDCommand constructor.
     *
     * @param Site   $site                Site
     * @param string $ccNumber            CC number
     * @param string $cvv                 CVV
     * @param string $expirationMonth     Expiration month
     * @param string $expirationYear      Expiration year
     * @param string $sessionId           Decoded token
     * @param string $deviceFingerprintId Device fingerprint id
     */
    public function __construct(
        Site $site,
        string $ccNumber,
        string $cvv,
        string $expirationMonth,
        string $expirationYear,
        string $sessionId,
        string $deviceFingerprintId
    ) {
        $this->site                = $site;
        $this->ccNum               = $ccNumber;
        $this->cvv                 = $cvv;
        $this->expirationMonth     = $expirationMonth;
        $this->expirationYear      = $expirationYear;
        $this->sessionId           = $sessionId;
        $this->deviceFingerprintId = $deviceFingerprintId;
    }

    /**
     * @return Site
     */
    public function site(): Site
    {
        return $this->site;
    }

    /**
     * @return string
     */
    public function ccNumber(): string
    {
        return $this->ccNum;
    }

    /**
     * @return string
     */
    public function cvv(): string
    {
        return $this->cvv;
    }

    /**
     * @return string
     */
    public function expirationMonth(): string
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function expirationYear(): string
    {
        return $this->expirationYear;
    }
    /**
     * @return string
     */
    public function sessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @return string
     */
    public function deviceFingerprintId(): string
    {
        return $this->deviceFingerprintId;
    }
}