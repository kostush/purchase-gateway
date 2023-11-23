<?php

namespace Tests\Unit\PurchaseGateway\Domain\Model\NextAction;

use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\NextAction\RedirectToUrl;
use ProBillerNG\PurchaseGateway\Domain\Model\ThirdParty;
use Tests\UnitTestCase;

class RedirectToUrlTest extends UnitTestCase
{
    const URL = 'https://purchase-gateway.probiller.com/api/v1/purchase/thirdParty/redirect/eyJ..';

    /**
     * @var MockObject|ThirdParty
     */
    private $thirdParty;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->thirdParty = $this->createMock(ThirdParty::class);
        $this->thirdParty->method('url')->willReturn(self::URL);
    }

    /**
     * @test
     * @return RedirectToUrl
     */
    public function it_should_return_a_redirect_to_url_object(): RedirectToUrl
    {
        $action = RedirectToUrl::create($this->thirdParty);

        $this->assertInstanceOf(RedirectToUrl::class, $action);

        return $action;
    }

    /**
     * @test
     * @depends it_should_return_a_redirect_to_url_object
     * @param RedirectToUrl $redirectToUrl Redirect to url.
     * @return void
     */
    public function it_should_contain_the_correct_third_party(RedirectToUrl $redirectToUrl): void
    {
        $this->assertEquals($this->thirdParty, $redirectToUrl->thirdParty());
    }

    /**
     * @test
     * @depends it_should_return_a_redirect_to_url_object
     * @param RedirectToUrl $redirectToUrl Redirect to url.
     * @return void
     */
    public function it_should_contain_the_exact_values_on_create(RedirectToUrl $redirectToUrl): void
    {
        $expectedResult = [
            'type'       => RedirectToUrl::TYPE,
            'thirdParty' => [
                'url' => self::URL
            ]
        ];

        $this->assertEquals($expectedResult, $redirectToUrl->toArray());
    }
}
