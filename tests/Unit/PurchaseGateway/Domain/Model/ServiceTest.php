<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use Tests\UnitTestCase;

class ServiceTest extends UnitTestCase
{
    /**
     * @test
     * @return Service
     * @throws \Exception
     */
    public function it_should_return_a_service_when_correct_data_is_sent(): Service
    {
        $options = [
            'templateId'  => 'fd027e3b-e7ec-415d-aedc-7740f3f8b1ec',
            'senderName'  => 'Probiller',
            'senderEmail' => 'welcome@probiller.com'
        ];

        $service = Service::create('MyService', true, $options);

        $this->assertInstanceOf(Service::class, $service);

        return $service;
    }

    /**
     * @test
     * @depends it_should_return_a_service_when_correct_data_is_sent
     * @param Service $service
     * @return void
     */
    public function it_should_return_a_service_as_array(Service $service): void
    {
        $this->assertIsArray($service->toArray());
    }

    /**
     * @test
     * @depends it_should_return_a_service_when_correct_data_is_sent
     * @param Service $service
     * @return void
     */
    public function it_should_return_a_service_with_correct_name(Service $service): void
    {
        $this->assertSame('MyService', $service->toArray()['name']);
    }

    /**
     * @test
     * @depends it_should_return_a_service_when_correct_data_is_sent
     * @param Service $service
     * @return void
     */
    public function it_should_return_a_service_with_correct_enable_type(Service $service): void
    {
        $this->assertSame(true, $service->toArray()['enabled']);
    }

    /**
     * @test
     * @depends it_should_return_a_service_when_correct_data_is_sent
     * @param Service $service
     * @return void
     */
    public function it_should_return_a_service_with_correct_options(Service $service): void
    {
        $this->assertSame(
            [
                'templateId'  => 'fd027e3b-e7ec-415d-aedc-7740f3f8b1ec',
                'senderName'  => 'Probiller',
                'senderEmail' => 'welcome@probiller.com'
            ],
            $service->toArray()['options']);
    }
}
