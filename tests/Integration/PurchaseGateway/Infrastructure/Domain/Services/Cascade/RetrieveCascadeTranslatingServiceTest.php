<?php

namespace Tests\Integration\PurchaseGateway\Infrastructure\Domain\Services\Cascade;

use ProBillerNG\PurchaseGateway\Domain\Model\Cascade;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Cascade\RetrieveCascadeTranslatingService;
use ProBillerNG\PurchaseGateway\Infrastructure\Services\Cascade\CascadeAdapter;
use Tests\IntegrationTestCase;

class RetrieveCascadeTranslatingServiceTest extends IntegrationTestCase
{
    /**
     * @test
     * @return void
     */
    public function it_should_return_a_cascade(): void
    {
        $translatingService = new RetrieveCascadeTranslatingService($this->app->make(CascadeAdapter::class));
        $result             = $translatingService->retrieveCascadeForInitPurchase(
            $this->faker->uuid,
            '1',
            '07402fb6-f8d6-11e8-8eb2-f2801f1b9fd1',
            'US',
            'cc',
            'visa',
            'ALL'
        );


        $this->assertInstanceOf(Cascade::class, $result);
    }
}
