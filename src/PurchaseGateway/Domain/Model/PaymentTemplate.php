<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class PaymentTemplate extends BasePaymentTemplate
{
    public const IDENTITY_VERIFICATION_METHOD = 'last4';

    public const CHEQUE_IDENTITY_VERIFICATION_METHOD = 'accountNumberLast4';

    /**
     * @var string|null
     */
    private $firstSix;

    /**
     * @var string|null
     */
    private $lastFour;

    /**
     * @var string|null
     */
    private $expirationYear;

    /**
     * @var string|null
     */
    private $expirationMonth;

    /**
     * @var string
     */
    private $lastUsedDate;

    /**
     * @var array
     */
    private $billerFields;

    /**
     * @var bool
     */
    private $isSelected;

    /**
     * @var string
     */
    private $createdAt;

    /**
     * @var string
     */
    private $label;

    /**
     * PaymentTemplate constructor.
     *
     * @param string      $templateId      Template id.
     * @param string|null $firstSix        First six.
     * @param string|null $lastFour        Last four.
     * @param string|null $expirationYear  Expiration year.
     * @param string|null $expirationMonth Expiration month.
     * @param string      $lastUsedDate    Last used date.
     * @param string      $createdAt       Created at
     * @param string      $billerName      Biller name.
     * @param array       $billerFields    Biller fields.
     * @param string|null $label
     */
    private function __construct(
        string $templateId,
        ?string $firstSix,
        ?string $lastFour,
        ?string $expirationYear,
        ?string $expirationMonth,
        string $lastUsedDate,
        string $createdAt,
        string $billerName,
        array $billerFields,
        ?string $label
    ) {
        $this->templateId      = $templateId;
        $this->firstSix        = $firstSix;
        $this->lastFour        = $lastFour;
        $this->expirationYear  = $expirationYear;
        $this->expirationMonth = $expirationMonth;
        $this->lastUsedDate    = $lastUsedDate;
        $this->createdAt       = $createdAt;
        $this->billerName      = $billerName;
        $this->billerFields    = $billerFields;
        $this->isSafe          = false;
        $this->isSelected      = false;
        $this->label           = $label;
    }

    /**
     * @param string      $templateId      Template id.
     * @param string|null $firstSix        First six.
     * @param string|null $lastFour        Last four.
     * @param string|null $expirationYear  Expiration year.
     * @param string|null $expirationMonth Expiration month.
     * @param string      $lastUsedDate    Last used date.
     * @param string      $createdAt       Created at
     * @param string      $billerName      Biller name.
     * @param array       $billerFields    Biller fields.
     * @param string|null $label
     *
     * @return PaymentTemplate
     */
    public static function create(
        string $templateId,
        ?string $firstSix,
        ?string $lastFour,
        ?string $expirationYear,
        ?string $expirationMonth,
        string $lastUsedDate,
        string $createdAt,
        string $billerName,
        array $billerFields,
        ?string $label = null
    ): self {
        return new static(
            $templateId,
            $firstSix,
            $lastFour,
            $expirationYear,
            $expirationMonth,
            $lastUsedDate,
            $createdAt,
            $billerName,
            $billerFields,
            $label
        );
    }

    /**
     * @return string|null
     */
    public function firstSix(): ?string
    {
        return $this->firstSix;
    }

    /**
     * @return string|null
     */
    public function lastFour(): ?string
    {
        return $this->lastFour;
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
    public function expirationMonth(): string
    {
        return $this->expirationMonth;
    }

    /**
     * @return string
     */
    public function lastUsedDate(): string
    {
        return $this->lastUsedDate;
    }

    /**
     * @return string
     */
    public function createdAt(): string
    {
        return $this->createdAt;
    }

    /**
     * @return array|null
     */
    public function billerFields(): ?array
    {
        return $this->billerFields;
    }

    /**
     * @return bool
     */
    public function isSelected(): bool
    {
        return $this->isSelected;
    }

    /**
     * @param bool $isSelected Is selected
     * @return void
     */
    public function setIsSelected(bool $isSelected): void
    {
        $this->isSelected = $isSelected;
    }

    /**
     * @param string|null $label
     *
     * @return void
     */
    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function label(): ?string
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $returnArray = [
            'templateId'                   => $this->templateId,
            'firstSix'                     => $this->firstSix,
            'expirationYear'               => $this->expirationYear,
            'expirationMonth'              => $this->expirationMonth,
            'lastUsedDate'                 => $this->lastUsedDate,
            'createdAt'                    => $this->createdAt,
            'requiresIdentityVerification' => !$this->isSafe,
            'identityVerificationMethod'   => self::IDENTITY_VERIFICATION_METHOD,
        ];

        // Label unavailable for non-ach purchases in MGPG response
        if (!empty($this->label)) {
            $returnArray['label'] = $this->label;
        }

        // billerName unavailable in MGPG response
        if ($this->billerName !== UnknownBiller::BILLER_NAME && $this->billerName !== '') {
            $returnArray['billerName'] = $this->billerName;
        }

        return $returnArray;
    }
}
