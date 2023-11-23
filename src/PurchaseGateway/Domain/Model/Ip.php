<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException;

class Ip
{
    /**
     * @var string
     */
    private $ip;

    /**
     * Ip constructor.
     * @param string $ip Ip
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(string $ip)
    {
        $this->initIp($ip);
    }

    /**
     * @param string $ip Ip
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     * @return void
     */
    private function initIp(string $ip): void
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpException;
        }

        $this->ip = $ip;
    }

    /**
     * @param string $ip Ip
     * @return Ip
     * @throws InvalidIpException
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(string $ip): self
    {
        return new static($ip);
    }

    /**
     * @return string
     */
    public function ip(): string
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->ip;
    }

    /**
     * @param Ip $ip Ip
     * @return bool
     */
    public function equals(Ip $ip): bool
    {
        return $this->ip() === $ip->ip();
    }
}
