<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use Tests\UnitTestCase;

class OverridesTest extends UnitTestCase
{

    /** @var array */
    private $payload;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->payload = [
            'subject'       => $this->faker->text,
            'friendlyName'  => $this->faker->name
        ];
    }

    /**
     * @test
     * @return array
     */
    public function it_should_create_a_valid_object_given_a_valid_payload(): array
    {
        $overrides = Overrides::create(
            $this->payload['subject'],
            $this->payload['friendlyName']
        );
        $this->assertInstanceOf(Overrides::class, $overrides);

        return [$overrides, $this->payload];
    }

    /**
     * @test
     * @depends it_should_create_a_valid_object_given_a_valid_payload
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_a_valid_subject_when_given_a_valid_object(array $objectAndPayload): void
    {
        /** @var $object Overrides */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($object->toArray()['subject'], $payload['subject']);
    }

    /**
     * @test
     * @depends it_should_create_a_valid_object_given_a_valid_payload
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_contain_a_valid_from_name_when_given_a_valid_object(array $objectAndPayload): void
    {
        /** @var $object Overrides */
        list($object, $payload) = $objectAndPayload;
        $this->assertEquals($object->toArray()['friendlyName'], $payload['friendlyName']);
    }
}
