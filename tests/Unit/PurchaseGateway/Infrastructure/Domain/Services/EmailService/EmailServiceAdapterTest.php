<?php
declare(strict_types=1);

namespace Tests\Unit\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use PHPUnit\Framework\MockObject\MockObject;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceAdapter;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceClient;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\EmailServiceTranslator;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\SendEmailResponse;
use Tests\UnitTestCase;

class EmailServiceAdapterTest extends UnitTestCase
{
    /** @var array */
    private $payload;

    /** @var EmailServiceClient|MockObject */
    private $client;

    /** @var EmailServiceTranslator|MockObject */
    private $translator;

    /**
     * @return void
     * @throws \ProBillerNG\Logger\Exception
     * @throws \ProBillerNG\PurchaseGateway\Domain\Model\Exception\InvalidUserInfoEmail
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
        $sendEmailResponseDtoMock = $this->createMock(SendEmailResponseDto::class);
        $this->client             = $this->getMockBuilder(EmailServiceClient::class)
            ->disableOriginalConstructor()
            ->setMethods(['send'])
            ->getMock();
        $this->client->method('send')->willReturn($sendEmailResponseDtoMock);
        $sendEmailResponseMock = $this->createMock(SendEmailResponse::class);
        $this->translator      = $this->getMockBuilder(EmailServiceTranslator::class)
            ->disableOriginalConstructor()
            ->setMethods(['translate'])
            ->getMock();
        $this->translator->method('translate')->willReturn($sendEmailResponseMock);
    }

    /**
     * @test
     * @throws \Exception
     * @return void
     */
    public function it_should_call_send_once_if_given_a_valid_payload(): void
    {
        $emailServiceAdapter = new EmailServiceAdapter($this->client, $this->translator);
        $this->client->expects($this->once())->method('send');
        $emailServiceAdapter->send(
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
     * @throws \Exception
     * @return void
     */
    public function it_should_call_translate_once_if_given_a_valid_payload(): void
    {
        $emailServiceAdapter = new EmailServiceAdapter($this->client, $this->translator);
        $this->translator->expects($this->once())->method('translate');
        $emailServiceAdapter->send(
            $this->payload['templateId'],
            $this->payload['email'],
            $this->payload['data'],
            $this->payload['overrides'],
            $this->payload['sessionId'],
            $this->payload['senderId']
        );
    }

}
