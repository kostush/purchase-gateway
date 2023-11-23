<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail;

class Email
{
    /**
     * @var string
     */
    private $email;

    /**
     * Email constructor.
     * @param string $email Email
     * @throws InvalidUserInfoEmail
     * @throws \ProBillerNG\Logger\Exception
     */
    private function __construct(string $email)
    {
        $this->initEmail($email);
    }

    /**
     * @param string $email Email
     * @return Email
     * @throws InvalidUserInfoEmail
     * @throws \ProBillerNG\Logger\Exception
     */
    public static function create(string $email)
    {
        return new static($email);
    }

    /**
     * @param string $email email
     * @return void
     * @throws InvalidUserInfoEmail
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initEmail(string $email): void
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL, FILTER_FLAG_EMAIL_UNICODE)) {
            throw new InvalidUserInfoEmail();
        }
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->email;
    }

    /**
     * @param Email $email Email
     * @return bool
     */
    public function equals(Email $email)
    {
        return (string) $this == (string) $email;
    }
    
    public function domain()
    {
        $explodeEmail = explode('@', $this->email);
        return array_pop($explodeEmail);
    }
}
