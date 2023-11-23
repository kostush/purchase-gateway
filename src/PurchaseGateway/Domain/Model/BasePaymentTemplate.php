<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

abstract class BasePaymentTemplate
{
    /**
     * @var string
     */
    protected $billerName;

    /**
     * @var string
     */
    protected $templateId;

    /**
     * @var bool
     */
    protected $isSafe;

    /**
     * @param bool $isSafe Is safe flag
     * @return void
     */
    public function setIsSafe(bool $isSafe): void
    {
        $this->isSafe = $isSafe;
    }

    /**
     * @return bool
     */
    public function isSafe(): bool
    {
        return $this->isSafe;
    }

    /**
     * @return string
     */
    public function templateId(): string
    {
        return $this->templateId;
    }

    /**
     * @return string
     */
    public function billerName(): string
    {
        return $this->billerName;
    }
}
