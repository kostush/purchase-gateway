<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Api\EmailApi;
use CommonServices\EmailServiceClient\Configuration;
use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions\ClientCouldNotSendEmailServiceException;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use Tests\UnitTestCase;

class EmailServiceClientTest extends UnitTestCase
{

    /**
     *
     * string $templateId,
     * Email $email,
     * array $data,
     * Overrides $overrides,
     * SessionId $sessionId,
     * string $senderId
     */

    /** @var array */
    private $payload;

    /** @var EmailApi|MockObject */
    private $emailApi;

    /**
     * @return void
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\ValidationException
     * @throws \ReflectionException
     * @throws \Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->payload = [
            'templateId' => $this->faker->word,
            'email'      => Email::create($this->faker->email),
            'data'       => [],
            'overrides'  => Overrides::create(
                $this->faker->text,
                $this->faker->name
            ),
            'sessionId'  => SessionId::createFromString($this->faker->uuid),
            'senderId'   => (string) $this->faker->randomNumber(),
        ];
        //
        $mockedSendEmailResponseDto = $this->createMock(SendEmailResponseDto::class);
        $configMock                 = $this->createMock(Configuration::class);
        $this->emailApi             = $this->getMockBuilder(EmailApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['sendEmail', 'getConfig'])
            ->getMock();
        $this->emailApi->method('sendEmail')->willReturn($mockedSendEmailResponseDto);
        $this->emailApi->method('getConfig')->willReturn($configMock);
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_call_send_once_when_given_a_valid_payload(): void
    {
        $emailServiceClient = new EmailServiceClient($this->emailApi);
        $this->emailApi->expects($this->once())->method('sendEmail');
        $emailServiceClient->send(
            $this->payload['templateId'],
            $this->payload['email'],
            $this->payload['data'],
            $this->payload['overrides'],
            $this->payload['sessionId'],
            $this->payload['senderId']
        );
    }

    /**
     * @test
     * @return void
     * @throws \Exception
     */
    public function it_should_throw_exception_if_given_invalid_payload(): void
    {
        $this->expectException(ClientCouldNotSendEmailServiceException::class);

        $this->emailApi->method('sendEmail')->willThrowException(new \Exception());
        $emailServiceClient = new EmailServiceClient($this->emailApi);
        $emailServiceClient->send(
            $this->payload['templateId'],
            $this->payload['email'],
            $this->payload['data'],
            $this->payload['overrides'],
            $this->payload['sessionId'],
            $this->payload['senderId']
        );
    }
}
