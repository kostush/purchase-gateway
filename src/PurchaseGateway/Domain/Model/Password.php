<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword;

class Password
{
    const MIN_PASSWORD_LENGTH = 1;
    const MAX_PASSWORD_LENGTH = 60;

    /** @var string */
    private $password;

    /**
     * Username constructor.
     * @param string|null $password Username
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPassword
     */
    private function __construct(?string $password)
    {
        $this->initPassword($password);
    }

    /**
     * @param string|null $password password
     * @return void
     * @throws InvalidUserInfoPassword
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initPassword(?string $password): void
    {
        if (empty($password)
            || mb_strlen($password) < self::MIN_PASSWORD_LENGTH
            || mb_strlen($password) > self::MAX_PASSWORD_LENGTH
            || preg_match('/[\d]{16}/', $password)
        ) {
            throw new InvalidUserInfoPassword();
        }
        $this->password = $password;
    }

    /**
     * @param string|null $password Username
     * @return Password
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPassword
     */
    public static function create(?string $password): self
    {
        return new self(
            $password
        );
    }

    /**
     * @return string|null
     */
    public function __toString(): ?string
    {
        return $this->password;
    }
}
