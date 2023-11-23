<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\MemberProfile;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberId;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\Username;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileGatewayTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\RetrieveMemberProfileServiceAdapter;
use Tests\UnitTestCase;

class MemberProfileGatewayTranslatingServiceTest extends UnitTestCase
{
    /**
     * string
     */
    const UUID = 'db577af6-b2ae-11e9-a2a3-2a2ae2dbcce4';

    /**
     * @var RetrieveMemberProfileServiceAdapter
     */
    private $retrieveMemberProfileAdapterMock;

    /**
     * @var MemberProfileGatewayTranslatingService
     */
    private $memberProfileTranslatingService;

    /**
     * Setup method
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     */
    public function setUp(): void
    {
        parent::setUp();

        $memberProfile = MemberInfo::create(
            MemberId::createFromString(self::UUID),
            Email::create($this->faker->email),
            Username::create('username123'),
            $this->faker->firstName,
            $this->faker->lastName
        );

        $this->retrieveMemberProfileAdapterMock = $this->createMock(RetrieveMemberProfileServiceAdapter::class);
        $this->retrieveMemberProfileAdapterMock->method('retrieveMemberProfile')->willReturn($memberProfile);
    }

    /**
     * @test
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     */
    public function it_should_return_a_member_profile_when_retrieve_member_profile_method_is_called(): void
    {
        $this->memberProfileTranslatingService = new MemberProfileGatewayTranslatingService(
            $this->retrieveMemberProfileAdapterMock
        );

        $result = $this->memberProfileTranslatingService->retrieveMemberProfile(
            self::UUID,
            $this->faker->uuid,
            $this->faker->word,
            $this->faker->uuid,
            $this->faker->uuid
        );

        $this->assertInstanceOf(MemberInfo::class, $result);
    }
}
