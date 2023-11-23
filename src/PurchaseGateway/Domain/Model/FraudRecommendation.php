<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * Class FraudRecommendation
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 */
class FraudRecommendation
{
    public const BLACKLIST                  = 100;
    public const CAPTCHA                    = 200;
    public const FORCE_THREE_D              = 300;
    public const BYPASS_TEMPLATE_VALIDATION = 400;
    public const NO_ACTION                  = 1000;

    public const DEFAULT_SEVERITY = 'Allow';
    public const DEFAULT_MESSAGE  = 'Allow_Transaction';
    public const DEFAULT_CODE     = self::NO_ACTION;

    public const BLOCK = 'block';
    
    public const CAPTCHA_SEVERITY = self::BLOCK;
    public const CAPTCHA_MESSAGE = 'Show_Captcha';

    /**
     * @var int
     */
    private $code;
    /**
     * @var string
     */
    private $severity;
    /**
     * @var string
     */
    private $message;
    /**
     * @var bool
     */
    private $isDefault;

    /**
     * FraudRecommendation constructor.
     * @param int    $code      Code
     * @param string $severity  Severity
     * @param string $message   Message
     * @param bool   $isDefault IsDefault
     */
    private function __construct(int $code, string $severity, string $message, ?bool $isDefault = false)
    {
        $this->code     = $code;
        $this->severity = $severity;
        $this->message  = $message;
        $this->isDefault = $isDefault;
    }

    /**
     * @param int    $code     Code
     * @param string $severity Severity
     * @param string $message  Message
     * @return FraudRecommendation
     */
    public static function create(int $code, string $severity, string $message)
    {
        return new static($code, $severity, $message);
    }

    /**
     * @return FraudRecommendation
     */
    public static function createDefaultAdvice()
    {
        return new static(
            self::DEFAULT_CODE,
            self::DEFAULT_SEVERITY,
            self::DEFAULT_MESSAGE,
            true
        );
    }

    /**
     * @return FraudRecommendation
     */
    public static function createCaptchaAdvice()
    {
        return new static(
            self::CAPTCHA,
            self::CAPTCHA_SEVERITY,
            self::CAPTCHA_MESSAGE
        );
    }

    /**
     * @return int
     */
    public function code(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function severity(): string
    {
        return $this->severity;
    }

    /**
     * @return bool
     */
    public function isSeverityBlock(): bool
    {
        return strtolower($this->severity()) === self::BLOCK;
    }

    /**
     * @return bool
     */
    public function isSoftBlock(): bool
    {
        if ($this->isSeverityBlock() && ($this->code() === self::CAPTCHA || Force3dsCodes::isValid($this->code())) ) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isHardBlock(): bool
    {
        if ($this->isSeverityBlock() && !$this->isSoftBlock()) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return $this->message;
    }

    /**
     * @return void
     */
    public function resetToDefaultIfThreeDForced(): void
    {
        if (!$this->isThreeDForced()) {
            return;
        }

        $this->code     = self::DEFAULT_CODE;
        $this->severity = self::DEFAULT_SEVERITY;
        $this->message  = self::DEFAULT_MESSAGE;
    }

    /**
     * @return bool
     */
    private function isThreeDForced(): bool
    {
        return Force3dsCodes::isValid($this->code());
    }

    /**
     * @return bool
     */
    public function isBypassTemplateValidation(): bool
    {
        return $this->code() === self::BYPASS_TEMPLATE_VALIDATION;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'severity' => $this->severity(),
            'code'     => $this->code(),
            'message'  => $this->message()
        ];
    }
}
