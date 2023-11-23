<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class EpochBillerFields implements BillerFields
{
    public const POSTBACK_ID = 'f5e23b47-7955-4449-9cba-0d363ba71ec2';

    /**
     * @var string
     */
    private $clientId;

    /**
     * @var string
     */
    private $clientKey;

    /**
     * @var string
     */
    private $clientVerificationKey;

    /**
     * EpochBillerFields constructor.
     * @param string $clientId              Client id
     * @param string $clientKey             Client key
     * @param string $clientVerificationKey Client verification key
     */
    private function __construct(
        string $clientId,
        string $clientKey,
        string $clientVerificationKey
    ) {
        $this->clientId              = $clientId;
        $this->clientKey             = $clientKey;
        $this->clientVerificationKey = $clientVerificationKey;
    }

    /**
     * @param string $clientId              Client id
     * @param string $clientKey             Client key
     * @param string $clientVerificationKey Client verification key
     * @return EpochBillerFields
     */
    public static function create(
        string $clientId,
        string $clientKey,
        string $clientVerificationKey
    ): self {
        return new self($clientId, $clientKey, $clientVerificationKey);
    }

    /**
     * @return string
     */
    public function clientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return string
     */
    public function clientKey(): string
    {
        return $this->clientKey;
    }

    /**
     * @return string
     */
    public function clientVerificationKey(): string
    {
        return $this->clientVerificationKey;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'clientId'              => $this->clientId(),
            'clientKey'             => $this->clientKey(),
            'clientVerificationKey' => $this->clientVerificationKey()
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
