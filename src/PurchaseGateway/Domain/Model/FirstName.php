<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName;

class FirstName
{
    public const MIN_NAME_LENGTH = 2;
    public const MAX_NAME_LENGTH = 20;

    /**
     * @var string
     */
    private $firstName;

    /**
     * Username constructor.
     * @param string $firstName FirstName
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoFirstName
     */
    private function __construct(string $firstName)
    {
        $this->initFirstName($firstName);
    }

    /**
     * @param string $firstName name
     * @return void
     * @throws InvalidUserInfoFirstName
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initFirstName(string $firstName): void
    {
        // we need to allow space only, but we still don't allow the following white space
        // \r matches a carriage return (ASCII 13)
        // \n matches a line-feed (newline) character (ASCII 10)
        // \t matches a tab character (ASCII 9)
        // \f matches a form-feed character (ASCII 12)
        if (mb_strlen($firstName) < self::MIN_NAME_LENGTH
            || mb_strlen($firstName) > self::MAX_NAME_LENGTH
            || preg_match('/[\r\n\t\f]/', $firstName)
        ) {
            throw new InvalidUserInfoFirstName();
        }
        $this->firstName = $firstName;
    }

    /**
     * @param string $firstName FirstName
     * @return FirstName
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoFirstName
     */
    public static function create(string $firstName): self
    {
        return new self(
            $firstName
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->firstName;
    }
}
