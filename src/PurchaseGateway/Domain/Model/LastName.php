<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName;

class LastName
{
    public const MIN_NAME_LENGTH = 2;
    public const MAX_NAME_LENGTH = 20;

    /**
     * @var string
     */
    private $lastName;

    /**
     * Username constructor.
     * @param string $firstName FirstName
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoLastName
     */
    private function __construct(string $firstName)
    {
        $this->initLastName($firstName);
    }

    /**
     * @param string $name name
     * @return void
     * @throws InvalidUserInfoLastName
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initLastName(string $name): void
    {
        // we need to allow normal space only, but we still don't allow the following white space
        // \r matches a carriage return (ASCII 13)
        // \n matches a line-feed (newline) character (ASCII 10)
        // \t matches a tab character (ASCII 9)
        // \f matches a form-feed character (ASCII 12)
        if (mb_strlen($name) < self::MIN_NAME_LENGTH
            || mb_strlen($name) > self::MAX_NAME_LENGTH
            || preg_match('/[\r\n\t\f]/', $name)
        ) {
            throw new InvalidUserInfoLastName();
        }
        $this->lastName = $name;
    }

    /**
     * @param string $lastName FirstName
     * @return LastName
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoLastName
     */
    public static function create(string $lastName): self
    {
        return new self(
            $lastName
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->lastName;
    }
}
