<?php

declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Model;

use ProBillerNG\PurchaseGateway\Domain\Model\Service;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceCollection;
use Tests\UnitTestCase;

class ServiceCollectionTest extends UnitTestCase
{

    /**
     * @test
     * @return ServiceCollection
     */
    public function it_should_return_a_service_collection_when_correct_data_is_sent(): ServiceCollection
    {
        $serviceCollection = new ServiceCollection();

        $services = [
            [
                'name'    => 'MyService 1',
                'enabled' => true,
                'options' => [
                    'templateId'  => '01a45b3e-75bc-446c-9bae-a30edb8ea45f',
                    'senderName'  => 'Probiller',
                    'senderEmail' => 'welcome@probiller.com'
                ]
            ],
            [
                'name'    => 'MyService 1',
                'enabled' => true,
                'options' => [
                    'templateId'  => '2bfe17fc-3f26-48e4-b38b-6cb70a3738f4',
                    'senderName'  => 'Mindgeek',
                    'senderEmail' => 'welcome@mindgeek.com'
                ]
            ]
        ];

        foreach ($services as $key => $item) {
            if (empty($item)) {
                break;
            }

            $serviceCollection->add(Service::create($item['name'], $item['enabled'], $item['options']));
        }

        $this->assertInstanceOf(ServiceCollection::class, $serviceCollection);

        return $serviceCollection;
    }

    /**
     * @test
     * @depends it_should_return_a_service_collection_when_correct_data_is_sent
     * @param ServiceCollection $serviceCollection Service collection
     * @return void
     */
    public function it_should_return_a_service_collection_as_array(ServiceCollection $serviceCollection): void
    {
        $this->assertIsArray($serviceCollection->toArray());
    }

    /**
     * @test
     * @depends it_should_return_a_service_collection_when_correct_data_is_sent
     * @param ServiceCollection $serviceCollection Service collection
     * @return void
     */
    public function it_should_return_a_service_collection_with_two_services(ServiceCollection $serviceCollection): void
    {
        $this->assertCount(2, $serviceCollection->toArray());
    }
}
