<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber;

class PhoneNumber
{
    /** @var string */
    private $phoneNumber;

    /**
     * Username constructor.
     * @param string $phoneNumber PhoneNumber
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPhoneNumber
     */
    private function __construct(string $phoneNumber)
    {
        $this->initPhoneNumber($phoneNumber);
    }

    /**
     * @param string $phoneNumber phoneNumber
     * @return void
     * @throws InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\Logger\Exception
     */
    private function initPhoneNumber(?string $phoneNumber)
    {
        if (!empty($phoneNumber)) {
            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

            if (!is_numeric($phoneNumber)) {
                throw new InvalidUserInfoPhoneNumber();
            }
        }
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @param string $phoneNumber PhoneNumber
     * @return PhoneNumber
     * @throws \ProBillerNG\Logger\Exception
     * @throws InvalidUserInfoPhoneNumber
     */
    public static function create(string $phoneNumber): self
    {
        return new self(
            $phoneNumber
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->phoneNumber;
    }
}
