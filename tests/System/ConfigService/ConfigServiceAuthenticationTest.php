<?php

namespace ConfigService;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\ConfigService;
use Tests\SystemTestCase;

class ConfigServiceAuthenticationTest extends SystemTestCase
{
    /**
     * @test
     */
    public function should_return_well_formatted_metadata(): void
    {
        /**
         * @var ConfigService $configService
         */
        $configService = $this->app->make(ConfigService::class);
        $data = ConfigService::getMetadata();
        $this->assertIsArray($data);
        $this->assertArrayHasKey('Authorization', $data);
        $this->assertIsArray($data['Authorization']);
        $this->assertStringContainsString('Bearer', $data['Authorization'][0]);
    }
}