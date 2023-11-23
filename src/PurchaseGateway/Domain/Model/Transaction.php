<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\BillerNotSupportedException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidThreeDVersionException;
use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidTransactionStateException;

class Transaction
{
    /**
     * Available transaction status codes
     */
    public const STATUS_APPROVED = 'approved';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_ABORTED  = 'aborted';
    public const STATUS_PENDING  = 'pending';

    public const AVAILABLE_STATUS_CODES = [
        self::STATUS_DECLINED,
        self::STATUS_APPROVED,
        self::STATUS_ABORTED,
        self::STATUS_PENDING
    ];

    /**
     * @var TransactionId
     */
    private $transactionId;

    /**
     * @var string
     */
    private $state;

    /**
     * @var BinRouting|null
     */
    private $successfulBinRouting;

    /**
     * @var string
     */
    private $billerName;

    /**
     * @var bool|null
     */
    private $newCCUsed;

    /**
     * @var null|string
     */
    private $acs;

    /**
     * @var null|string
     */
    private $pareq;

    /**
     * @var null|string
     */
    private $redirectUrl;

    /**
     * @var bool|null
     */
    private $isNsf;

    /**
     * @var ErrorClassification|null
     */
    private $errorClassification;

    /**
     * @var string|null
     */
    private $errorCode;

    /**
     * Transaction constructor.
     *
     * @param null|TransactionId $transactionId Transaction Id
     * @param string             $state         State
     * @param string             $billerName    The biller name used for this transaction
     * @param bool|null          $newCCUsed     New credit card used
     * @param null|string        $acs           The ACS
     * @param null|string        $pareq         The PAREQ
     * @param null|string        $redirectUrl   The third party biller redirect url
     * @param bool|null          $isNsf         Flag isNsf
     *
     * @var null|string
     */
    private $deviceCollectionUrl;

    /**
     * @var null|string
     */
    private $deviceCollectionJwt;

    /**
     * @var null|string
     */
    private $deviceFingerprintId;

    /**
     * @var null|string
     */
    private $threeDStepUpUrl;

    /**
     * @var null|string
     */
    private $threeDStepUpJwt;

    /**
     * Rocketgate biller transaction id.
     *
     * @var null|string
     */
    private $md;

    /**
     * @var bool
     */
    private $threeDFrictionless;

    /**
     * @var null|string
     */
    private $threeDVersion;

    /**
     * @var null|string
     */
    private $threeDPaymentLinkUrl;

    /**
     * Transaction constructor.
     * @param null|TransactionId       $transactionId        Transaction Id
     * @param string                   $state                State
     * @param string                   $billerName           The biller name used for this transaction
     * @param bool|null                $newCCUsed            New credit card used
     * @param null|string              $acs                  The ACS
     * @param null|string              $pareq                The PAREQ
     * @param null|string              $redirectUrl          The third party biller redirect url
     * @param bool|null                $isNsf                isNsf
     * @param null|string              $deviceCollectionUrl  Device collection url
     * @param null|string              $deviceCollectionJwt  Device collection jwt
     * @param ErrorClassification|null $errorClassification  Error classification
     * @param string|null              $errorCode            Error code
     * @param null|string              $threeDPaymentLinkUrl The 3D payment link url
     * @throws \Exception
     */
    private function __construct(
        ?TransactionId $transactionId,
        string $state,
        string $billerName,
        ?bool $newCCUsed,
        ?string $acs,
        ?string $pareq,
        ?string $redirectUrl,
        ?bool $isNsf,
        ?string $deviceCollectionUrl,
        ?string $deviceCollectionJwt,
        ?ErrorClassification $errorClassification = null,
        ?string $errorCode = null,
        ?string $threeDPaymentLinkUrl = null
    ) {
        $this->transactionId = $transactionId;
        $this->setState($state);
        $this->setBillerName($billerName);
        $this->newCCUsed            = $newCCUsed;
        $this->acs                  = $acs;
        $this->pareq                = $pareq;
        $this->redirectUrl          = $redirectUrl;
        $this->isNsf                = $isNsf;
        $this->deviceCollectionUrl  = $deviceCollectionUrl;
        $this->deviceCollectionJwt  = $deviceCollectionJwt;
        $this->threeDFrictionless   = false;
        $this->errorClassification  = $errorClassification;
        $this->errorCode            = $errorCode;
        $this->threeDPaymentLinkUrl = $threeDPaymentLinkUrl;
    }

    /**
     * @param string $billerName The biller name
     * @return void
     * @throws \Exception
     */
    private function setBillerName(string $billerName): void
    {
        switch ($billerName) {
            case UnknownBiller::BILLER_NAME:
            case RocketgateBiller::BILLER_NAME:
            case NetbillingBiller::BILLER_NAME:
            case EpochBiller::BILLER_NAME:
            case QyssoBiller::BILLER_NAME:
                $this->billerName = $billerName;
                break;
            default:
                throw new BillerNotSupportedException(null, $billerName);
        }
    }

    /**
     * @param string $state The transaction status
     * @return void
     * @throws \Exception
     */
    public function setState(string $state): void
    {
        if (!in_array($state, self::AVAILABLE_STATUS_CODES)) {
            throw new InvalidTransactionStateException($state);
        }

        $this->state = $state;
    }

    /**
     * @param null|TransactionId       $transactionId        Transaction Id
     * @param string                   $state                StateBi
     * @param string                   $billerName           The biller name used for this transaction
     * @param bool|null                $newCCUsed            New credit card used
     * @param null|string              $acs                  The ACS
     * @param null|string              $pareq                The PAREQ
     * @param null|string              $redirectUrl          The third party redirect url
     * @param bool|null                $isNsf                Flag isNsf
     * @param null|string              $deviceCollectionUrl  Device collection url
     * @param null|string              $deviceCollectionJwt  Device collection jwt
     * @param ErrorClassification|null $errorClassification  Error classification for declined transactions
     * @param string|null              $errorCode            Error Code
     * @param null|string              $threeDPaymentLinkUrl The 3D payment link url
     * @return Transaction
     * @throws \Exception
     */
    public static function create(
        ?TransactionId $transactionId,
        string $state,
        string $billerName,
        ?bool $newCCUsed = null,
        ?string $acs = null,
        ?string $pareq = null,
        ?string $redirectUrl = null,
        ?bool $isNsf = null,
        ?string $deviceCollectionUrl = null,
        ?string $deviceCollectionJwt = null,
        ?ErrorClassification $errorClassification = null,
        ?string $errorCode = null,
        ?string $threeDPaymentLinkUrl = null
    ): self {
        return new static(
            $transactionId,
            $state,
            $billerName,
            $newCCUsed,
            $acs,
            $pareq,
            $redirectUrl,
            $isNsf,
            $deviceCollectionUrl,
            $deviceCollectionJwt,
            $errorClassification,
            $errorCode,
            $threeDPaymentLinkUrl
        );
    }

    /**
     * @return null|TransactionId
     */
    public function transactionId(): ?TransactionId
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return BinRouting|null
     */
    public function successfulBinRouting(): ?BinRouting
    {
        return $this->successfulBinRouting;
    }

    /**
     * @param BinRouting|null $binRouting The bin routing object
     * @return void
     */
    public function addSuccessfulBinRouting(?BinRouting $binRouting): void
    {
        $this->successfulBinRouting = $binRouting;
    }

    /**
     * @return bool
     */
    public function isApproved(): bool
    {
        return $this->state() === self::STATUS_APPROVED;
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state() === self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isAborted(): bool
    {
        return $this->state() === self::STATUS_ABORTED;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }

    /**
     * @param bool $newCCWasUsed A new cc was used or not
     * @return void
     */
    public function setNewCCUsed(bool $newCCWasUsed): void
    {
        $this->newCCUsed = $newCCWasUsed;
    }

    /**
     * @return bool|null
     */
    public function newCCUsed(): ?bool
    {
        return $this->newCCUsed;
    }

    /**
     * @return bool|null
     */
    public function isNsf(): ?bool
    {
        return $this->isNsf;
    }

    /**
     * @param string $acs Acs.
     * @return  void
     */
    public function setAcs(string $acs): void
    {
        $this->acs = $acs;
    }

    /**
     * @return null|string
     */
    public function acs(): ?string
    {
        return $this->acs;
    }

    /**
     * @param string $pareq Pareq.
     * @return void.
     */
    public function setPareq(string $pareq): void
    {
        $this->pareq = $pareq;
    }

    /**
     * @return null|string
     */
    public function pareq(): ?string
    {
        return $this->pareq;
    }

    /**
     * @return null|string
     */
    public function redirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @return null|string
     */
    public function threeDPaymentLinkUrl(): ?string
    {
        return $this->threeDPaymentLinkUrl;
    }

    /**
     * @return string|null
     */
    public function deviceCollectionUrl(): ?string
    {
        return $this->deviceCollectionUrl;
    }

    /**
     * @return null|string
     */
    public function deviceCollectionJwt(): ?string
    {
        return $this->deviceCollectionJwt;
    }

    /**
     * @param null|string $deviceFingerprintId Device fingerprint id.
     * @return void
     */
    public function setDeviceFingerprintId(?string $deviceFingerprintId): void
    {
        $this->deviceFingerprintId = $deviceFingerprintId;
    }

    /**
     * @return string|null
     */
    public function deviceFingerprintId(): ?string
    {
        return $this->deviceFingerprintId;
    }

    /**
     * @param null|string $threeDStepUpUrl ThreeD step up url.
     * @return void
     */
    public function setThreeDStepUpUrl(?string $threeDStepUpUrl): void
    {
        $this->threeDStepUpUrl = $threeDStepUpUrl;
    }

    /**
     * @param ErrorClassification $errorClassification ErrorClassification
     * @return void
     */
    public function setErrorClassification(ErrorClassification $errorClassification): void
    {
        $this->errorClassification = $errorClassification;
    }

    /**
     * @return string
     */
    public function threeDStepUpUrl(): ?string
    {
        return $this->threeDStepUpUrl;
    }

    /**
     * @param string|null $threeDStepUpJwt ThreeD step up url.
     * @return  void
     */
    public function setThreeDStepUpJwt(?string $threeDStepUpJwt): void
    {
        $this->threeDStepUpJwt = $threeDStepUpJwt;
    }

    /**
     * @return string|null
     */
    public function threeDStepUpJwt(): ?string
    {
        return $this->threeDStepUpJwt;
    }

    /**
     * @param string|null $md Biller transaction id.
     * @return void
     */
    public function setMd(?string $md): void
    {
        $this->md = $md;
    }

    /**
     * @return string|null
     */
    public function md(): ?string
    {
        return $this->md;
    }

    /**
     * @param bool $isFrictionless Is frictionless.
     * @return void.
     */
    public function setThreeDFrictionless(bool $isFrictionless): void
    {
        $this->threeDFrictionless = $isFrictionless;
    }

    /**
     * @return bool
     */
    public function threeDFrictionless(): bool
    {
        return $this->threeDFrictionless;
    }

    /**
     * @param null|int $threeDVersion ThreeD secure version.
     * @return void
     * @throws InvalidThreeDVersionException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function setThreeDVersion(?int $threeDVersion): void
    {
        if ($threeDVersion !== null && !in_array($threeDVersion, ThreeD::VERSIONS)) {
            throw new InvalidThreeDVersionException($threeDVersion);
        }

        $this->threeDVersion = $threeDVersion;
    }

    /**
     * @return int|null
     */
    public function threeDVersion(): ?int
    {
        return $this->threeDVersion;
    }


    /**
     * @return ErrorClassification|null
     */
    public function errorClassification(): ?ErrorClassification
    {
        return $this->errorClassification;
    }

    /**
     * @return bool
     */
    public function errorClassificationHasHardType(): bool
    {
        if ($this->errorClassification instanceof ErrorClassification
            && $this->errorClassification->toArray()['errorType'] == ErrorClassification::ERROR_TYPE_HARD
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return string|null
     */
    public function errorCode(): ?string
    {
        return $this->errorCode;
    }
}
