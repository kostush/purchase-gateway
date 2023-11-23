<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Model;

class MemberInfo
{
    /**
     * @var MemberId
     */
    private $memberId;

    /**
     * @var Username|null
     */
    private $username;

    /**
     * @var Email
     */
    private $email;

    /**
     * @var string|null
     */
    private $firstName;

    /**
     * @var string|null
     */
    private $lastName;

    /**
     * MemberInfo constructor.
     * @param MemberId      $memberId  MemberId
     * @param Email         $email     email
     * @param Username|null $username  user name
     * @param string|null   $firstName first name
     * @param string|null   $lastName  last name
     */
    private function __construct(
        MemberId $memberId,
        Email $email,
        ?Username $username = null,
        ?string $firstName = null,
        ?string $lastName = null
    ) {
        $this->memberId  = $memberId;
        $this->username  = $username ?? null;
        $this->email     = $email;
        $this->firstName = $firstName ?? null;
        $this->lastName  = $lastName ?? null;
    }

    /**
     * @param MemberId      $memberId  MemberId
     * @param Email         $email     email
     * @param Username|null $username  User name
     * @param string|null   $firstName first name
     * @param string|null   $lastName  last name
     * @return self
     */
    public static function create(
        MemberId $memberId,
        Email $email,
        ?Username $username = null,
        ?string $firstName = null,
        ?string $lastName = null
    ): self {
        return new self(
            $memberId,
            $email,
            $username,
            $firstName,
            $lastName
        );
    }

    /**
     * @return MemberId
     */
    public function memberId(): MemberId
    {
        return $this->memberId;
    }

    /**
     * @return Username|Null
     */
    public function username(): ?Username
    {
        return $this->username;
    }

    /**
     * @return string|null
     */
    public function firstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return string|null
     */
    public function lastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @return Email
     */
    public function email(): Email
    {
        return $this->email;
    }

    /**
     * @param Username $username The username
     * @return void
     */
    public function setUsername(Username $username): void
    {
        $this->username = $username;
    }

    /**
     * @param Email $email The email
     * @return void
     */
    public function setEmail(Email $email): void
    {
        $this->email = $email;
    }


    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'memberId' => (string) $this->memberId(),
            'email'    => (string) $this->email(),
            'username' => (string) $this->username(),
        ];
    }
}
