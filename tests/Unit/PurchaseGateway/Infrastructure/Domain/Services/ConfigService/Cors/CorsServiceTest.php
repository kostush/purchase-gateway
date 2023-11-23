<?php

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\ConfigService\Cors\CorsService;
use Tests\UnitTestCase;

class CorsServiceTest extends UnitTestCase
{

    /**
     * @test
     * @return void
     */
    public function it_should_return_well_formatted_cors_domain_string()
    {
        $this->assertEquals('*.domain.com', CorsService::formatDomain('http://www.domain.com'));
        $this->assertEquals('*.domain.com', CorsService::formatDomain('http://domain.com'));
        $this->assertEquals('*.domain.com', CorsService::formatDomain('domain.com'));
        $this->assertEquals('*.domain', CorsService::formatDomain('domain'));
        $this->assertEquals('', CorsService::formatDomain(''));
    }
}
