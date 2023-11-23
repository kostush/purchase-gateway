<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

/**
 * @codeCoverageIgnore
 */
class ThreeDAuthenticateUrl
{
    /** @var string */
    private $authenticateUrl;

    /**
     * ThreeDAuthenticateUrl constructor.
     * @param string $authenticateUrl Authenticate url
     */
    private function __construct(string $authenticateUrl)
    {
        $this->authenticateUrl = $authenticateUrl;
    }

    /**
     * @param string $authenticateUrl Authenticate url
     * @return ThreeDAuthenticateUrl
     */
    public static function create(string $authenticateUrl): self
    {
        return new static($authenticateUrl);
    }

    /**
     * @return string
     */
    public function authenticateUrl(): string
    {
        return $this->authenticateUrl;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->authenticateUrl();
    }
}
