<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class PublicKey
{
    /**
     * @var KeyId
     */
    private $key;

    /**
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * @param KeyId              $key       The KeyId object
     * @param \DateTimeImmutable $createdAt The creation date
     * @return void
     */
    private function __construct(KeyId $key, \DateTimeImmutable $createdAt)
    {
        $this->key       = $key;
        $this->createdAt = $createdAt;
    }

    /**
     * @param KeyId              $key       The KeyId object
     * @param \DateTimeImmutable $createdAt The creation date
     * @return PublicKey
     */
    public static function create(KeyId $key, \DateTimeImmutable $createdAt): self
    {
        return new static($key, $createdAt);
    }

    /**
     * @return KeyId
     */
    public function key(): KeyId
    {
        return $this->key;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
