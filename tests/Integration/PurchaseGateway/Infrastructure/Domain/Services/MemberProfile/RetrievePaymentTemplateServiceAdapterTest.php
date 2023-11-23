<?php

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\MemberProfile;

use ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse200;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileGatewayErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileGatewayClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\RetrieveMemberProfileServiceAdapter;
use Tests\IntegrationTestCase;

class RetrievePaymentTemplateServiceAdapterTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     * @throws MemberProfileGatewayErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileGatewayTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileNotFoundException
     */
    public function it_should_return_a_member_info_when_retrieve_member_profile_method_is_called(): void
    {
        $clientMock = $this->getMockBuilder(MemberProfileGatewayClient::class)
            ->setMethods(['retrieveMemberProfile'])
            ->disableOriginalConstructor()
            ->getMock();

        $clientMock->method('retrieveMemberProfile')->willReturn(
            new InlineResponse200(
                [
                    'memberId'  => $this->faker->uuid,
                    'email'     => $this->faker->email,
                    'firstName' => $this->faker->firstName,
                    'lastName'  => $this->faker->lastName
                ]
            )
        );

        $retrieveMemberProfileAdapter = new RetrieveMemberProfileServiceAdapter(
            $clientMock,
            new MemberProfileTranslator()
        );

        $result = $retrieveMemberProfileAdapter->retrieveMemberProfile(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->word,
            $this->faker->uuid,
            $this->faker->uuid,
            null
        );

        $this->assertInstanceOf(MemberInfo::class, $result);
    }

    /**
     * @test
     * @return void
     * @throws MemberProfileGatewayErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileGatewayTypeException
     * @throws \ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileNotFoundException
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     */
    public function it_should_throw_exception_when_client_call_fails(): void
    {
        $this->expectException(MemberProfileGatewayErrorException::class);

        $clientMock = $this->getMockBuilder(MemberProfileGatewayClient::class)
            ->setMethods(['retrieveMemberProfile'])
            ->disableOriginalConstructor()
            ->getMock();

        $clientMock->method('retrieveMemberProfile')->willThrowException(new MemberProfileGatewayErrorException());

        $memberProfileAdapter = new RetrieveMemberProfileServiceAdapter(
            $clientMock,
            new MemberProfileTranslator()
        );

        $memberProfileAdapter->retrieveMemberProfile(
            $this->faker->uuid,
            $this->faker->uuid,
            $this->faker->word,
            $this->faker->uuid,
            $this->faker->uuid,
            null
        );
    }
}
