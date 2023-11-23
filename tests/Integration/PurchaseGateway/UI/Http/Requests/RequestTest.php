<?php

namespace Tests\Integration\PurchaseGateway\UI\Http\Requests;

use phpDocumentor\Reflection\Types\Void_;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\InitPurchaseRequest;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\ProcessPurchaseRequest;
use ProBillerNG\PurchaseGateway\UI\Http\Requests\Request;
use Tests\IntegrationTestCase;

/**
 * Class ProcessPurchaseControllerTest
 * @package Tests\Integration\PurchaseGateway\UI\Http\Controllers
 */
class RequestTest extends IntegrationTestCase
{
    /**
     * @test
     */
    public function it_should_return_mapped_fraud_headers()
    {
        /** @var MockObject|InitPurchaseRequest $request */
        $request = $this->getMockBuilder(InitPurchaseRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['header'])
            ->getMock();

        $request->expects($this->any())
            ->method('header')
            ->willReturn([
                'x-51d-browsername'       => ['Chrome'],
                'x-51d-browserversion'    => ['Unknown'],
                'x-51d-platformname'      => ['Unknown'],
                'x-51d-platformversion'   => ['Unknown'],
                'x-51d-devicetype'        => ['Desktop'],
                'x-51d-ismobile'          => ['False'],
                'x-51d-hardwaremodel'     => ['Unknown'],
                'x-51d-hardwarefamily'    => ['Emulator'],
                'x-51d-javascript'        => ['Unknown'],
                'x-51d-javascriptversion' => ['Unknown'],
                'x-51d-viewport'          => ['True'],
                'x-51d-html5'             => ['True'],
                'x-51d-iscrawler'         => ['True'],
                'x-geo-connection-type'   => ['Corporate'],
                'x-geo-isp'               => ['9219-1568 Quebec'],
                'x-anonymous-type'        => ['None'],
            ]);

        $fraudHeaders = $request->getFraudRequiredHeaders();

        $this->assertEquals(
            [
                'browserName'       => ['Chrome'],
                'browserVersion'    => ['Unknown'],
                'platformName'      => ['Unknown'],
                'platformVersion'   => ['Unknown'],
                'deviceType'        => ['Desktop'],
                'isMobile'          => ['False'],
                'hardwareModel'     => ['Unknown'],
                'hardwareFamily'    => ['Emulator'],
                'javascript'        => ['Unknown'],
                'javascriptVersion' => ['Unknown'],
                'viewport'          => ['True'],
                'html5'             => ['True'],
                'isCrawler'         => ['True'],
                'connectionType'    => ['Corporate'],
                'isp'               => ['9219-1568 Quebec'],
                'anonymousType'     => ['None'],
            ],
            $fraudHeaders
        );
    }

    /**
     * @test
     */
    public function it_should_return_empty_mapped_fraud_headers()
    {
        /** @var MockObject|InitPurchaseRequest $request */
        $request = $this->getMockBuilder(InitPurchaseRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['header'])
            ->getMock();

        $request->expects($this->any())
            ->method('header')
            ->willReturn([]);

        $fraudHeaders = $request->getFraudRequiredHeaders();

        $this->assertEmpty($fraudHeaders);
    }

    /**
     * @test
     */
    public function it_should_return_mapped_fraud_headers_only_for_existing_headers()
    {
        /** @var MockObject|InitPurchaseRequest $request */
        $request = $this->getMockBuilder(InitPurchaseRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['header'])
            ->getMock();

        $request->expects($this->any())
            ->method('header')
            ->willReturn([
                'x-51d-viewport'          => ['True'],
                'x-51d-html5'             => ['True'],
                'x-51d-iscrawler'         => ['True'],
                'x-geo-connection-type'   => ['Corporate'],
                'x-geo-isp'               => ['9219-1568 Quebec'],
                'x-anonymous-type'        => ['None'],
            ]);

        $fraudHeaders = $request->getFraudRequiredHeaders();

        $this->assertEquals(
            [
                'viewport'          => ['True'],
                'html5'             => ['True'],
                'isCrawler'         => ['True'],
                'connectionType'    => ['Corporate'],
                'isp'               => ['9219-1568 Quebec'],
                'anonymousType'     => ['None'],
            ],
            $fraudHeaders
        );
    }

    /**
     * @test
     * @dataProvider checkSavingAccountProvider
     */
    public function it_should_return_bool_savingAccount_value($input, $expected)
    {
        /** @var MockObject|ProcessPurchaseRequest $request */
        $request = $this->getMockBuilder(ProcessPurchaseRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['json'])
            ->getMock();

        $request->expects($this->any())
            ->method('json')
            ->willReturn($input);

        $this->assertEquals($expected, $request->savingAccount());
    }

    /**
     * @return array
     */
    public function checkSavingAccountProvider(): array
    {
        return [
            "string_false"  => ["false", false],
            "boolean_false" => [false, false],
            "string_true"   => ["true", true],
            "boolean_true"  => [true, true],
            "any_int"       => [345, false],
            "any_string"    => ["RandomString", false],
        ];
    }
}
