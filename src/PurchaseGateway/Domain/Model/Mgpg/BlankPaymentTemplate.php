<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Mgpg;

use ProBillerNG\PurchaseGateway\Domain\Model\BasePaymentTemplate;
use ProBillerNG\PurchaseGateway\Domain\Model\UnknownBiller;

class BlankPaymentTemplate extends BasePaymentTemplate
{
    public const IDENTITY_VERIFICATION_METHOD_NONE = '';

    /**
     * @var string
     */
    private $createdAt;

    /**
     * @var string
     */
    private $lastUsedDate;

    /**
     * @param string $templateId
     */
    public function __construct(
        string $templateId
    ) {
        $this->templateId   = $templateId;
        $this->billerName   = UnknownBiller::BILLER_NAME;
        $this->createdAt    = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->lastUsedDate = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    /**
     * @param string $templateId   Template id.
     * @return BlankPaymentTemplate
     */
    public static function create(
        string $templateId
    ): self {
        return new static($templateId);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'templateId'                   => $this->templateId,
            'lastUsedDate'                 => $this->lastUsedDate,
            'createdAt'                    => $this->createdAt,
            'requiresIdentityVerification' => !$this->isSafe,
            'identityVerificationMethod'   => self::IDENTITY_VERIFICATION_METHOD_NONE,
        ];
    }
}