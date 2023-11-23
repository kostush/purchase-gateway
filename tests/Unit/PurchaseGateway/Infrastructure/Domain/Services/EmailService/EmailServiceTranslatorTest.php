<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions\UndefinedTranslationObjectGiven;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\SendEmailResponse;
use Tests\UnitTestCase;

class EmailServiceTranslatorTest extends UnitTestCase
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
            'SendEmailResponseDto' => new SendEmailResponseDto(
                [
                    'sessionId' => (string) $this->faker->uuid,
                    'traceId'   => (string) $this->faker->uuid,
                ]
            )
        ];
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_return_a_valid_object_if_given_a_valid_payload(): void
    {
        $emailServiceTranslator = new EmailServiceTranslator();
        $this->assertInstanceOf(
            SendEmailResponse::class,
            $emailServiceTranslator->translate($this->payload['SendEmailResponseDto'])
        );
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_throw_exception_if_given_an_invalid_payload(): void
    {
        $emailServiceTranslator = new EmailServiceTranslator();
        $this->expectException(UndefinedTranslationObjectGiven::class);
        $emailServiceTranslator->translate(SessionId::create());
    }
}
