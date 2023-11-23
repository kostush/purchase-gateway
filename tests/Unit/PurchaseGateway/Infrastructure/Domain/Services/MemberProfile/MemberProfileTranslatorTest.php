<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\MemberProfile;

use ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse200;
use ProbillerNG\MemberProfileGatewayClient\Model\InlineResponse400;
use ProBillerNG\PurchaseGateway\Domain\Model\MemberInfo;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\Exceptions\MemberProfileGatewayErrorException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\MemberProfileGateway\MemberProfileTranslator;
use Tests\UnitTestCase;

class MemberProfileTranslatorTest extends UnitTestCase
{
    /**
     * @var MemberProfileTranslator
     */
    private $translator;

    /**
     * Setup function
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->translator = new MemberProfileTranslator();
    }

    /**
     * @test
     * @return void
     * @throws MemberProfileGatewayErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoPassword
     */
    public function it_should_throw_exception_for_bad_request_error_result(): void
    {
        $this->expectException(MemberProfileGatewayErrorException::class);

        $result = new InlineResponse400(
            [
                'code'  => 1,
                'error' => 'Bad request error'
            ]
        );

        $this->translator->translateRetrieveMemberInfo($this->faker->uuid, $result, null, null);
    }

    /**
     * @test
     * @throws MemberProfileGatewayErrorException
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoUsername
     * @return void
     */
    public function it_should_return_a_member_info(): void
    {
        $result = new InlineResponse200(
            [
                'memberId' => $this->faker->uuid,
                'email'    => $this->faker->email,
                'firstName' => $this->faker->firstName,
                'lastName' => $this->faker->lastName
            ]
        );

        $result = $this->translator->translateRetrieveMemberInfo($this->faker->uuid, $result, null, null);

        $this->assertInstanceOf(MemberInfo::class, $result);
    }
}
