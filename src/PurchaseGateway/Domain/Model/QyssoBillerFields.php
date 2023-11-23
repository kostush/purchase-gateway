<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class QyssoBillerFields implements BillerFields
{
    public const POSTBACK_ID = '4c430aeb-e034-4a1b-8f7c-3dbaf90744f9';

    /**
     * @var string
     */
    private $companyNum;

    /**
     * @var string
     */
    private $personalHashKey;

    /**
     * QyssoBillerFields constructor.
     * @param string $companyNum      Company Number
     * @param string $personalHashKey Personal Hash key
     */
    private function __construct(
        string $companyNum,
        string $personalHashKey
    ) {
        $this->companyNum      = $companyNum;
        $this->personalHashKey = $personalHashKey;
    }

    /**
     * @param string $companyNum      Company Number
     * @param string $personalHashKey Personal Hash key
     * @return QyssoBillerFields
     */
    public static function create(
        string $companyNum,
        string $personalHashKey
    ): self {
        return new self($companyNum, $personalHashKey);
    }

    /**
     * @return string
     */
    public function companyNum(): string
    {
        return $this->companyNum;
    }

    /**
     * @return string
     */
    public function personalHashKey(): string
    {
        return $this->personalHashKey;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'companyNum'      => $this->companyNum(),
            'personalHashKey' => $this->personalHashKey(),
        ];
    }

    /**
     * @return string
     */
    public function postbackId(): string
    {
        return self::POSTBACK_ID;
    }
}
