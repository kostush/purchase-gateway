<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Application\Services\NuData;

use ProBillerNG\PurchaseGateway\Application\NuData\NuDataAccountInfoData;
use Tests\UnitTestCase;

class NuDataAccountInfoDataTest extends UnitTestCase
{
    /**
     * @var NuDataAccountInfoData
     */
    private $nuDataAccountInfoData;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->nuDataAccountInfoData = $this->createNuDataAccountInfoData();
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_username(): void
    {
        $this->assertEquals('username', $this->nuDataAccountInfoData->username());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_password(): void
    {
        $this->assertEquals('password', $this->nuDataAccountInfoData->password());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_email(): void
    {
        $this->assertEquals('email@mindgeek.com', $this->nuDataAccountInfoData->email());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_first_name(): void
    {
        $this->assertEquals('Mister', $this->nuDataAccountInfoData->firstName());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_last_name(): void
    {
        $this->assertEquals('Axe', $this->nuDataAccountInfoData->lastName());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_phone(): void
    {
        $this->assertEquals('514-000-0911', $this->nuDataAccountInfoData->phone());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_address(): void
    {
        $this->assertEquals('123 Random Street Hello Boulevard', $this->nuDataAccountInfoData->address());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_city(): void
    {
        $this->assertEquals('Montreal', $this->nuDataAccountInfoData->city());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_state(): void
    {
        $this->assertEquals(null, $this->nuDataAccountInfoData->state());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_country(): void
    {
        $this->assertEquals('CA', $this->nuDataAccountInfoData->country());
    }

    /**
     * @test
     * @return void
     */
    public function it_should_return_zip_code(): void
    {
        $this->assertEquals('h1h1h1', $this->nuDataAccountInfoData->zipCode());
    }
}