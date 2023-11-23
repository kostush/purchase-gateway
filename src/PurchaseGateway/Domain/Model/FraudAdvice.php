<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Application\Exceptions\CannotValidateProcessCaptchaWithoutInitCaptchaException;

/**
 * Class FraudAdvice
 * @package ProBillerNG\PurchaseGateway\Domain\Model
 * @deprecated true
 */
class FraudAdvice
{
    const FOR_INIT    = 'init';
    const FOR_PROCESS = 'process';

    const MAX_TIMES_BLACKLISTED = 1;

    /**
     * @var Ip|null
     */
    private $ip;

    /**
     * @var Email|null
     */
    private $email;

    /**
     * @var Zip|null
     */
    private $zip;

    /**
     * @var Bin|null
     */
    private $bin;

    /**
     * @var bool
     */
    private $initCaptchaAdvised = false;

    /**
     * @var bool
     */
    private $initCaptchaValidated = false;

    /**
     * @var bool
     */
    private $processCaptchaAdvised = false;

    /**
     * @var bool
     */
    private $processCaptchaValidated = false;

    /**
     * @var bool
     */
    private $captchaAlreadyValidated = false;

    /**
     * @var bool
     */
    private $blacklistedOnInit = false;

    /**
     * @var bool
     */
    private $blacklistedOnProcess = false;

    /**
     * @var int
     */
    private $timesBlacklisted = 0;

    /**
     * @var bool
     */
    private $forceThreeDOnInit = false;

    /**
     * @var bool
     */
    private $forceThreeDOnProcess = false;

    /**
     * @var bool
     */
    private $detectThreeDUsage = false;

    /**
     * FraudAdvice constructor.
     * @param Ip|null    $ip    Ip
     * @param Email|null $email Email
     * @param Zip|null   $zip   Zip
     * @param Bin|null   $bin   Bin
     */
    private function __construct(?Ip $ip, ?Email $email, ?Zip $zip, ?Bin $bin)
    {
        $this->ip    = $ip;
        $this->email = $email;
        $this->zip   = $zip;
        $this->bin   = $bin;
    }

    /**
     * @param Ip|null    $ip    Ip
     * @param Email|null $email Email
     * @param Zip|null   $zip   Zip
     * @param Bin|null   $bin   Bin
     *
     * @return FraudAdvice
     */
    public static function create(?Ip $ip = null, ?Email $email = null, ?Zip $zip = null, ?Bin $bin = null): self
    {
        return new static($ip, $email, $zip, $bin);
    }

    /**
     * @param FraudAdvice $previousFraudAdvice Fraud Advice
     *
     * @return FraudAdvice
     */
    public static function createFromPreviousAdviceOnProcess(FraudAdvice $previousFraudAdvice): self
    {
        $newFraudAdvice = new static(
            $previousFraudAdvice->ip(),
            $previousFraudAdvice->email(),
            $previousFraudAdvice->zip(),
            $previousFraudAdvice->bin()
        );

        if ($previousFraudAdvice->isCaptchaAlreadyValidated()) {
            $newFraudAdvice->captchaAlreadyValidated = true;
        }

        if ($previousFraudAdvice->isInitCaptchaAdvised()) {
            $newFraudAdvice->initCaptchaAdvised = true;
        }

        if ($previousFraudAdvice->isInitCaptchaValidated()) {
            $newFraudAdvice->initCaptchaValidated = true;
        }

        if ($previousFraudAdvice->isProcessCaptchaAdvised()) {
            $newFraudAdvice->processCaptchaAdvised = true;
        }

        if ($previousFraudAdvice->isProcessCaptchaValidated()) {
            $newFraudAdvice->processCaptchaValidated = true;
        }

        if ($previousFraudAdvice->isBlacklistedOnInit()) {
            $newFraudAdvice->blacklistedOnInit = true;
        }

        if ($previousFraudAdvice->isForceThreeDOnInit()) {
            $newFraudAdvice->markForceThreeDOnInit();
        }

        if ($previousFraudAdvice->isForceThreeDOnProcess()) {
            $newFraudAdvice->markForceThreeDOnProcess();
        }

        if ($previousFraudAdvice->isDetectThreeDUsage()) {
            $newFraudAdvice->markDetectThreeDUsage();
        }

        $newFraudAdvice->timesBlacklisted = $previousFraudAdvice->timesBlacklisted();

        return $newFraudAdvice;
    }

    /**
     * @param FraudAdvice $processFraudAdvice Fraud advice
     * @return FraudAdvice
     */
    public function addProcessFraudAdvice(
        FraudAdvice $processFraudAdvice
    ): self {
        $newFraudAdvice = new static(
            $this->ip(),
            $processFraudAdvice->email(),
            $processFraudAdvice->zip(),
            $processFraudAdvice->bin()
        );

        if ($this->isCaptchaAlreadyValidated()) {
            $newFraudAdvice->captchaAlreadyValidated = true;
        }

        if ($this->isInitCaptchaAdvised()) {
            $newFraudAdvice->initCaptchaAdvised = true;
        }

        if ($this->isInitCaptchaValidated()) {
            $newFraudAdvice->initCaptchaValidated = true;
        }

        if ($this->isBlacklistedOnInit()) {
            $newFraudAdvice->blacklistedOnInit = true;
        }

        if ($processFraudAdvice->isProcessCaptchaAdvised()) {
            $newFraudAdvice->processCaptchaAdvised = true;
        }

        if ($processFraudAdvice->isBlacklistedOnProcess()) {
            $newFraudAdvice->blacklistedOnProcess = true;
        }

        if ($this->isForceThreeDOnInit()) {
            $newFraudAdvice->markForceThreeDOnInit();
        }

        if ($this->isForceThreeDOnProcess()) {
            $newFraudAdvice->markForceThreeDOnProcess();
        }

        if ($this->isDetectThreeDUsage()) {
            $newFraudAdvice->markDetectThreeDUsage();
        }

        $newFraudAdvice->timesBlacklisted = $this->timesBlacklisted();

        return $newFraudAdvice;
    }

    /**
     * @return Ip|null
     */
    public function ip(): ?Ip
    {
        return $this->ip;
    }

    /**
     * @return Email|null
     */
    public function email(): ?Email
    {
        return $this->email;
    }

    /**
     * @return Zip|null
     */
    public function zip(): ?Zip
    {
        return $this->zip;
    }

    /**
     * @return Bin|null
     */
    public function bin(): ?Bin
    {
        return $this->bin;
    }

    /**
     * @return bool
     */
    public function isInitCaptchaAdvised(): bool
    {
        return $this->initCaptchaAdvised;
    }

    /**
     * @return bool
     */
    public function isInitCaptchaValidated(): bool
    {
        return $this->initCaptchaValidated;
    }

    /**
     * @return bool
     */
    public function isProcessCaptchaAdvised(): bool
    {
        return $this->processCaptchaAdvised;
    }

    /**
     * @return bool
     */
    public function isProcessCaptchaValidated(): bool
    {
        return $this->processCaptchaValidated;
    }

    /**
     * @return bool
     */
    public function isCaptchaAlreadyValidated(): bool
    {
        return $this->captchaAlreadyValidated;
    }

    /**
     * @return bool
     */
    public function isBlacklistedOnInit(): bool
    {
        return $this->blacklistedOnInit;
    }

    /**
     * @return bool
     */
    public function isBlacklistedOnProcess(): bool
    {
        return $this->blacklistedOnProcess;
    }

    /**
     * @return int
     */
    public function timesBlacklisted(): int
    {
        return $this->timesBlacklisted;
    }

    /**
     * @return bool
     */
    public function isForceThreeDOnInit(): bool
    {
        return $this->forceThreeDOnInit;
    }

    /**
     * @return bool
     */
    public function isForceThreeDOnProcess(): bool
    {
        return $this->forceThreeDOnProcess;
    }

    /**
     * @return bool
     */
    public function isForceThreeD(): bool
    {
        return $this->isForceThreeDOnInit() || $this->isForceThreeDOnProcess();
    }

    /**
     * @return bool
     */
    public function isDetectThreeDUsage(): bool
    {
        return $this->detectThreeDUsage;
    }

    /**
     * @return void
     */
    public function markForceThreeDOnInit(): void
    {
        $this->forceThreeDOnInit = true;
    }

    /**
     * @return void
     */
    public function markForceThreeDOnProcess(): void
    {
        $this->forceThreeDOnProcess = true;
    }

    /**
     * @param FraudAdvice|null $fraudAdvice Previous FraudAdvice
     * @return void
     */
    public function markForceThreeDOnInitBasedOnAdvice(?FraudAdvice $fraudAdvice): void
    {
        if ($fraudAdvice === null) {
            return;
        }

        if (!$fraudAdvice->isForceThreeDOnInit()) {
            return;
        }

        $this->markForceThreeDOnInit();
    }

    /**
     * @return void
     */
    public function markDetectThreeDUsage(): void
    {
        $this->detectThreeDUsage = true;
    }

    /**
     * @return void
     */
    public function increaseTimesBlacklisted(): void
    {
        $this->timesBlacklisted++;
    }

    /**
     * @return void
     */
    public function markInitCaptchaAdvised(): void
    {
        $this->initCaptchaAdvised = true;
    }

    /**
     * @return void
     *
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validateInitCaptcha(): void
    {
        $this->initCaptchaValidated    = true;
        $this->captchaAlreadyValidated = true;

        if (!$this->isInitCaptchaAdvised()) {
            Log::warning('Validated init captcha even though it was not advised');
        }
    }

    /**
     * @return void
     */
    public function markProcessCaptchaAdvised(): void
    {
        $this->processCaptchaAdvised = true;
    }

    /**
     * @return void
     *
     * @throws CannotValidateProcessCaptchaWithoutInitCaptchaException
     * @throws \ProBillerNG\Logger\Exception
     */
    public function validateProcessCaptcha(): void
    {
        if ($this->isInitCaptchaAdvised() && !$this->isInitCaptchaValidated()) {
            throw new CannotValidateProcessCaptchaWithoutInitCaptchaException();
        }

        $this->processCaptchaValidated = true;
        $this->captchaAlreadyValidated = true;

        if (!$this->isProcessCaptchaAdvised()) {
            Log::warning('Validated process captcha even though it was not advised');
        }
    }

    /**
     * @return void
     */
    public function markBlacklistedOnInit(): void
    {
        $this->blacklistedOnInit = true;
    }

    /**
     * @return void
     */
    public function markBlacklistedOnProcess(): void
    {
        $this->blacklistedOnProcess = true;
    }

    /**
     * @param Email|null $email Email
     * @param Zip|null   $zip   Zip
     * @param Bin|null   $bin   Bin
     *
     * @return bool
     */
    public function fraudFieldsChanged(?Email $email, ?Zip $zip, ?Bin $bin): bool
    {
        return ((string) $this->email() !== (string) $email
                || (string) $this->zip() !== (string) $zip
                || (string) $this->bin() !== (string) $bin);
    }

    /**
     * @param Email|null $email Email
     * @param Zip|null   $zip   Zip
     * @param Bin|null   $bin   Bin
     *
     * @return array
     */
    public function getChangedFraudFields(?Email $email, ?Zip $zip, ?Bin $bin): array
    {
        $changedFields = [];

        if ((string) $this->email() !== (string) $email) {
            $changedFields['email'] = (string) $email;
        }

        if ((string) $this->zip() !== (string) $zip) {
            $changedFields['zip'] = (string) $zip;
        }

        if ((string) $this->bin() !== (string) $bin) {
            $changedFields['bin'] = (string) $bin;
        }

        return $changedFields;
    }

    /**
     * Check if captcha validated
     * @return bool
     */
    public function isCaptchaValidated(): bool
    {
        if ($this->isCaptchaAlreadyValidated()) {
            return true;
        }

        $isNotCaptchaValidatedOnInit = ($this->isInitCaptchaAdvised() && !$this->isInitCaptchaValidated());
        $isNotCaptchaValidatedOnProcess = ($this->isProcessCaptchaAdvised() && !$this->isProcessCaptchaValidated());

        if ($isNotCaptchaValidatedOnInit || $isNotCaptchaValidatedOnProcess) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function shouldBlockProcess(): bool
    {
        $isBlacklistedOnInit = $this->isBlacklistedOnInit();
        $maxBlacklisted = ($this->isBlacklistedOnProcess() && $this->timesBlacklisted() >= self::MAX_TIMES_BLACKLISTED);
        $notValidatedCaptcha = !$this->isCaptchaValidated();

        return $isBlacklistedOnInit || $maxBlacklisted || $notValidatedCaptcha;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'ip'                      => (string) $this->ip(),
            'email'                   => (string) $this->email(),
            'zip'                     => (string) $this->zip(),
            'bin'                     => (string) $this->bin(),
            'initCaptchaAdvised'      => $this->isInitCaptchaAdvised(),
            'initCaptchaValidated'    => $this->isInitCaptchaValidated(),
            'processCaptchaAdvised'   => $this->isProcessCaptchaAdvised(),
            'processCaptchaValidated' => $this->isProcessCaptchaValidated(),
            'blacklistedOnInit'       => $this->isBlacklistedOnInit(),
            'blacklistedOnProcess'    => $this->isBlacklistedOnProcess(),
            'captchaAlreadyValidated' => $this->isCaptchaAlreadyValidated(),
            'timesBlacklisted'        => $this->timesBlacklisted(),
            'forceThreeDOnInit'       => $this->isForceThreeDOnInit(),
            'forceThreeDOnProcess'    => $this->isForceThreeDOnProcess(),
            'forceThreeD'             => $this->isForceThreeD(),
            'detectThreeDUsage'       => $this->isDetectThreeDUsage()
        ];
    }
}
