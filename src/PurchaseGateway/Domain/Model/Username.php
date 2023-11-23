<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername;

class Username
{
    const MIN_USERNAME_LENGTH = 1;
    const MAX_USERNAME_LENGTH = 60;

    /** @var string */
    private $username;

    /**
     * Username constructor.
     * @param string|null $username Username
     * @throws InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(?string $username)
    {
        $this->initUsername($username);
    }

    /**
     * @param string|null $username username
     * @return void
     * @throws InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initUsername(?string $username): void
    {
        if (empty($username)
            || mb_strlen($username) < self::MIN_USERNAME_LENGTH
            || mb_strlen($username) > self::MAX_USERNAME_LENGTH
            || preg_match('/^[\d]{16}$/', $username)
        ) {
            throw new InvalidUserInfoUsername();
        }
        $this->username = $username;
    }

    /**
     * @param string|null $username Username
     * @return Username
     * @throws InvalidUserInfoUsername
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(?string $username): self
    {
        return new self($username);
    }

    /**
     * @return string|null
     */
    public function __toString(): ?string
    {
        return $this->username;
    }
}
