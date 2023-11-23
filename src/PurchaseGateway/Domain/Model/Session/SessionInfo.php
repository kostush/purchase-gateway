<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model\Session;

class SessionInfo
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $payload;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * SessionInfo constructor.
     * @param string    $id      Id
     * @param string    $payload Payload
     * @param \DateTime $created Created
     *
     * @throws \Exception
     */
    private function __construct(string $id, string $payload, \DateTime $created = null)
    {
        $this->id        = $id;
        $this->payload   = $payload;
        $this->createdAt = $created;
    }

    /**
     * @param string    $id      Id
     * @param string    $payload Payload
     * @param \DateTime $created Created
     * @return SessionInfo
     * @throws \Exception
     */
    public static function create(string $id, string $payload, \DateTime $created = null): self
    {
        return new static($id, $payload, $created);
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function payload(): string
    {
        return $this->payload;
    }

    /**
     * @return \DateTime
     */
    public function createdAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param array $payload Payload
     * @return void
     */
    public function setPayload(array $payload): void
    {
        $this->payload = json_encode($payload);
    }
}
