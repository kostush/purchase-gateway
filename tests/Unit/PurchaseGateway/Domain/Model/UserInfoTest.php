<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\CountryCode;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\FirstName;
use ProBillerNG\PurchaseGateway\Domain\Model\Ip;
use ProBillerNG\PurchaseGateway\Domain\Model\LastName;
use ProBillerNG\PurchaseGateway\Domain\Model\Password;
use ProBillerNG\PurchaseGateway\Domain\Model\PhoneNumber;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Domain\Model\Zip;
use Tests\UnitTestCase;

class UserInfoTest extends UnitTestCase
{
    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     */
    public function it_should_create_a_user_info_object_if_the_correct_data_is_provided(): array
    {
        $payload = [
            'country'             => CountryCode::create($this->faker->countryCode),
            'ipAddress'           => Ip::create($this->faker->ipv4),
            'username'            => Username::create('test1234'),
            'password'            => Password::create('pass12345'),
            'firstName'           => FirstName::create($this->faker->firstName),
            'lastName'            => LastName::create($this->faker->lastName),
            'email'               => Email::create($this->faker->email),
            'zipCode'             => Zip::create('80085'),
            'city'                => $this->faker->city,
            'state'               => $this->faker->city,
            'phoneNumber'         => PhoneNumber::create('514-000-0911'),
            'address'             => $this->faker->address,
            'billerMemberId'      => '113927096852',
            'countryDetectedByIp' => CountryCode::create($this->faker->countryCode)
        ];

        $userInfo = UserInfo::create(
            $payload['country'],
            $payload['ipAddress'],
            $payload['email'],
            $payload['username'],
            $payload['password'],
            $payload['firstName'],
            $payload['lastName'],
            $payload['zipCode'],
            $payload['city'],
            $payload['state'],
            $payload['phoneNumber'],
            $payload['address'],
            $payload['countryDetectedByIp']
        );
        $this->assertInstanceOf(UserInfo::class, $userInfo);
        return [$userInfo, $payload];
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload objectAndPayload
     * @return void
     */
    public function it_should_contain_username(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['username'], $object->username());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload objectAndPayload
     * @return void
     */
    public function it_should_contain_password(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['password'], $object->password());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload userInfo
     * @return void
     */
    public function it_should_contain_firstName(array $objectAndPayload)
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['firstName'], $object->firstName());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_lastName(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['lastName'], $object->lastName());
    }


    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_email(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['email'], $object->email());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_zipCode(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['zipCode'], $object->zipCode());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_city(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['city'], $object->city());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_state(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['state'], $object->state());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_country(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['country'], $object->countryCode());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_country_detected_by_ip(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['countryDetectedByIp'], $object->countryCodeDetectedByIp());
    }

    /**
     * @test
     * @depends it_should_create_a_user_info_object_if_the_correct_data_is_provided
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_phoneNumber(array $objectAndPayload): void
    {
        /** @var $object UserInfo */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($payload['phoneNumber'], $object->phoneNumber());
    }

    /**
     * @test
     * @return array
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidIpException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoCountry
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoFirstName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoLastName
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPhoneNumber
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidZipCodeException
     */
    public function it_should_create_a_user_info_object_if_the_correct_data_is_provided_even_with_non_latin_characters(): array
    {
        $payload = [
            'country'        => CountryCode::create($this->faker->countryCode),
            'ipAddress'      => Ip::create($this->faker->ipv4),
            'username'       => Username::create('test1234'),
            'password'       => Password::create('암호'),
            'firstName'      => FirstName::create('이름'),
            'lastName'       => LastName::create('이름'),
            'email'          => Email::create('이메일@test.mindgeek.com'),
            'zipCode'        => Zip::create('80085'),
            'city'           => '시티',
            'state'          => '상태',
            'phoneNumber'    => PhoneNumber::create('514-000-0911'),
            'address'        => '주소',
            'billerMemberId' => '113927096852'
        ];

        $userInfo = UserInfo::create(
            $payload['country'],
            $payload['ipAddress'],
            $payload['email'],
            $payload['username'],
            $payload['password'],
            $payload['firstName'],
            $payload['lastName'],
            $payload['zipCode'],
            $payload['city'],
            $payload['state'],
            $payload['phoneNumber'],
            $payload['address']
        );
        $this->assertInstanceOf(UserInfo::class, $userInfo);
        return [$userInfo, $payload];
    }
}
