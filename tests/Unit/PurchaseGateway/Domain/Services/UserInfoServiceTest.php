<?php

namespace Tests\Unit\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\PurchaseProcess;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use ProBillerNG\PurchaseGateway\Domain\Services\UserInfoService;
use Tests\UnitTestCase;

class UserInfoServiceTest extends UnitTestCase
{
    /**
     * @var PurchaseProcess
     */
    private $purchaseProcess;

    /**
     * @return void
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->purchaseProcess = $this->createMock(PurchaseProcess::class);
    }

    /**
     * @test
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @return UserInfo
     */
    public function it_should_set_the_first_name(): UserInfo
    {
        $userInfo = $this->createMock(UserInfo::class);
        $this->purchaseProcess->method('userInfo')->willReturn($userInfo);

        $service = new UserInfoService();
        $service->update(
            $this->purchaseProcess,
            [
                'name'  => 'John Snow',
                'email' => 'snow.john@winter.com',
                'zip'   => 'H0H0H0'
            ]
        );

        $userInfo->method('firstName')->willReturn(FirstName::create('John'));

        $this->assertSame('John', (string) $this->purchaseProcess->userInfo()->firstName());

        return $userInfo;
    }

    /**
     * @test
     * @param UserInfo $userInfo User info
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @depends it_should_set_the_first_name
     */
    public function it_should_set_the_last_name(UserInfo $userInfo): void
    {
        $userInfo->method('lastName')->willReturn(LastName::create('Snow'));

        $this->assertSame('Snow', (string) $userInfo->lastName());
    }

    /**
     * @test
     * @param UserInfo $userInfo User info
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @depends it_should_set_the_first_name
     */
    public function it_should_set_the_email(UserInfo $userInfo): void
    {
        $userInfo->method('email')->willReturn(Email::create('snow.john@winter.com'));

        $this->assertSame('snow.john@winter.com', (string) $userInfo->email());
    }

    /**
     * @test
     * @param UserInfo $userInfo User info
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     * @depends it_should_set_the_first_name
     */
    public function it_should_set_the_zip_code(UserInfo $userInfo): void
    {
        $userInfo->method('zipCode')->willReturn(Zip::create('H0H0H0'));

        $this->assertSame('H0H0H0', (string) $userInfo->zipCode());
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     */
    public function it_should_update_user_info_without_name(): void
    {
        $userInfo = $this->createMock(UserInfo::class);
        $this->purchaseProcess->method('userInfo')->willReturn($userInfo);

        $service = new UserInfoService();
        $service->update(
            $this->purchaseProcess,
            [
                'email' => 'new_email@domain.com',
                'zip'   => 'I0I0I0'
            ]
        );

        $this->assertSame('', (string) $this->purchaseProcess->userInfo()->firstName());
    }
}
