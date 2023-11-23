<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * @codeCoverageIgnore
 */
class ThreeD
{
    /**
     * Valid threeD versions
     */
    public const VERSIONS = [1, 2];

    /** @var bool */
    private $forceThreeDSecure;

    /** @var bool */
    private $detectThreeDUsage;

    /**
     * ThreeD constructor.
     * @param bool $forceThreeDSecure Force 3DS flag
     * @param bool $detectThreeDUsage Detect 3DS flag
     */
    private function __construct(
        bool $forceThreeDSecure,
        bool $detectThreeDUsage
    ) {
        $this->forceThreeDSecure = $forceThreeDSecure;
        $this->detectThreeDUsage = $detectThreeDUsage;
    }

    /**
     * @param bool $forceThreeDSecure Force 3DS flag
     * @param bool $detectThreeDUsage Detect 3DS flag
     * @return ThreeD
     */
    public static function create(
        bool $forceThreeDSecure,
        bool $detectThreeDUsage
    ): self {
        return new static(
            $forceThreeDSecure,
            $detectThreeDUsage
        );
    }

    /**
     * @return bool
     */
    public function forceThreeDSecure(): bool
    {
        return $this->forceThreeDSecure;
    }

    /**
     * @return bool
     */
    public function detectThreeDUsage(): bool
    {
        return $this->detectThreeDUsage;
    }
}
