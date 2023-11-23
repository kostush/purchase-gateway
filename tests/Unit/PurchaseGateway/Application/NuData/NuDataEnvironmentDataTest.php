<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataEnvironmentData;
use Tests\UnitTestCase;

class NuDataEnvironmentDataTest extends UnitTestCase
{
    /**
     * @var NuDataEnvironmentData
     */
    private $nuDataEnvironmentData;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataEnvironmentData = $this->createNuDataEnvironmentData();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_session_id(): void
    {
        $this->assertNotEmpty($this->nuDataEnvironmentData->ndSesssionId());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_nu_data_widget_data(): void
    {
        $this->assertEquals('{"ndWidgetData": "widget"}', $this->nuDataEnvironmentData->ndWidgetData());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_remote_ip(): void
    {
        $this->assertEquals('10.10.109.185', $this->nuDataEnvironmentData->remoteIp());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_request_url(): void
    {
        $this->assertEquals('/api/v1/purchase/process', $this->nuDataEnvironmentData->requestUrl());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_user_agent(): void
    {
        $this->assertEquals('PostmanRuntime/7.22.0', $this->nuDataEnvironmentData->userAgent());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_x_orwarded_for(): void
    {
        $this->assertEquals('192.168.16.1', $this->nuDataEnvironmentData->xForwardedFor());
    }
}