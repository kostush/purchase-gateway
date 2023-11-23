<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Domain\Projector\ItemToProject;

use ProBillerNG\PurchaseGateway\Domain\Projector\ItemToProject\AddonCreated;
use Tests\UnitTestCase;

class AddonCreatedTest extends UnitTestCase
{
    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_return_an_addon_created_from_received_event()
    {
        $data = [
            'id'          => 1,
            'eventBody'   => '{"aggregate_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","occurred_on":"2019-10-10T13:11:58+00:00","addon_id":"b81c6b98-a828-4e6d-b439-434cbb9fd39a","name":"Kip Gorczany V","type":"quis"}',
            'typeName'    => 'ProBillerNG\\BundleManagementAdmin\\Domain\\Model\\Event\\Addon\\AddonCreatedEvent',
            'aggregateId' => 'b81c6b98-a828-4e6d-b439-434cbb9fd39a',
            'occurredOn'  => '2019-10-10 13:11:58.000000',
        ];

        $addonCreated = new AddonCreated(
            $data['id'],
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $data['occurredOn']),
            $data['eventBody']
        );
        $this->assertInstanceOf(
            AddonCreated::class,
            $addonCreated
        );
    }
}
