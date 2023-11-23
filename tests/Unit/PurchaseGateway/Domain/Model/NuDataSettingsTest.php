<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\NuDataSettings;
use Tests\UnitTestCase;

class NuDataSettingsTest extends UnitTestCase
{
    /**
     * @test
     * @return NuDataSettings
     */
    public function it_should_return_a_nu_data_settings_object(): NuDataSettings
    {
        $nuDataSettings = $this->createNuDataSettings();

        $this->assertInstanceOf(NuDataSettings::class, $nuDataSettings);

        return $nuDataSettings;
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_have_the_correct_client_id(NuDataSettings $nuDataSettings): void
    {
        $this->assertEquals('w-123456', $nuDataSettings->clientId());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_have_the_correct_url(NuDataSettings $nuDataSettings): void
    {
        $this->assertEquals('https://api-mgk.nd.nudatasecurity.com/health/', $nuDataSettings->url());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_have_the_correct_value_for_enabled(NuDataSettings $nuDataSettings): void
    {
        $this->assertEquals(true, $nuDataSettings->enabled());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_be_an_array(NuDataSettings $nuDataSettings): void
    {
        $this->assertIsArray($nuDataSettings->toArray());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_contain_client_id_key(NuDataSettings $nuDataSettings): void
    {
        $this->assertArrayHasKey('clientId', $nuDataSettings->toArray());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_contain_url_key(NuDataSettings $nuDataSettings): void
    {
        $this->assertArrayHasKey('url', $nuDataSettings->toArray());
    }

    /**
     * @test
     * @param NuDataSettings $nuDataSettings NuData Settings
     * @depends it_should_return_a_nu_data_settings_object
     * @return void
     */
    public function it_should_contain_enabled_key(NuDataSettings $nuDataSettings): void
    {
        $this->assertArrayHasKey('enabled', $nuDataSettings->toArray());
    }
}
