<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\AuthenticateThreeD;
use ProBillerNG\PurchaseGateway\Domain\Model\ThreeDAuthenticateUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use Tests\UnitTestCase;

class AuthenticateThreeDTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_contain_version_1_and_the_exact_other_values_when_to_array_is_called(): void
    {
        $threeDAuthenticateUrl = $this->createMock(ThreeDAuthenticateUrl::class);
        $threeDAuthenticateUrl->method('authenticateUrl')->willReturn('some-uri');
        $threeDAuthenticateUrl->method('__toString')->willReturn('some-uri');

        $expectedResult = [
            'type'    => AuthenticateThreeD::TYPE,
            'version' => 1,
            'threeD'  => [
                'authenticateUrl' => 'some-uri'
            ]
        ];

        $action = AuthenticateThreeD::create($threeDAuthenticateUrl);
        $this->assertEquals($expectedResult, $action->toArray());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_contain_version_2_and_the_exact_other_values_when_to_array_is_called(): void
    {
        $threeDAuthenticateUrl = $this->createMock(ThreeDAuthenticateUrl::class);
        $threeDAuthenticateUrl->method('authenticateUrl')->willReturn('some-uri');
        $threeDAuthenticateUrl->method('__toString')->willReturn('some-uri');

        $transaction = $this->createMock(Transaction::class);
        $transaction->method('threeDVersion')->willReturn(2);
        $transaction->method('threeDStepUpUrl')->willReturn('threeD-step-up-url');
        $transaction->method('threeDStepUpJwt')->willReturn('threeD-step-up-jwt');
        $transaction->method('md')->willReturn('123');

        $expectedResult = [
            'type'    => AuthenticateThreeD::TYPE,
            'version' => 2,
            'threeD'  => [
                'authenticateUrl' => 'threeD-step-up-url',
                'jwt' => 'threeD-step-up-jwt',
                'md' => '123'
            ]
        ];

        $action = AuthenticateThreeD::create($threeDAuthenticateUrl, $transaction);
        $this->assertEquals($expectedResult, $action->toArray());
    }
}
