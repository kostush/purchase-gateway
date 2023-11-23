<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\SendEmailResponse;
use Tests\UnitTestCase;

class SendEmailResponseTest extends UnitTestCase
{
    private $payload;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'sessionId' => $this->faker->uuid,
            'traceId'   => $this->faker->uuid
        ];
    }

    /**
     * @test
     * @return array
     * @throws \Exception
     */
    public function it_should_create_a_valid_object_given_a_valid_payload()
    {
        $sendEmailResponse = SendEmailResponse::create(
            SessionId::createFromString($this->payload['sessionId']),
            $this->payload['traceId']
        );
        $this->assertInstanceOf(SendEmailResponse::class, $sendEmailResponse);

        return [$sendEmailResponse, $this->payload];
    }

    /**
     * @test
     * @depends it_should_create_a_valid_object_given_a_valid_payload
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_return_a_valid_session_ud_given_a_valid_object(array $objectAndPayload): void
    {
        /** @var $object SendEmailResponse */
        list($object,$payload) = $objectAndPayload;
        $this->assertEquals($payload['sessionId'], $object->sessionId());
    }

    /**
     * @test
     * @depends it_should_create_a_valid_object_given_a_valid_payload
     * @param array $objectAndPayload ObjectAndPayload
     * @return void
     */
    public function it_should_return_a_valid_trace_id_given_a_valid_object(array $objectAndPayload): void
    {
        /** @var $object SendEmailResponse */
        list($object,$payload) = $objectAndPayload;
        $this->assertEquals($payload['traceId'], $object->traceId());
    }
}
