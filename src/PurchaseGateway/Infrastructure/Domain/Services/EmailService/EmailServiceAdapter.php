<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailAdapter;

class EmailServiceAdapter implements EmailAdapter
{
    /** @var  EmailServiceClient */
    private $client;

    /** @var EmailServiceTranslator */
    private $translator;

    /**
     * EmailServiceAdapter constructor.
     * @param EmailServiceClient     $client     EmailServiceClient
     * @param EmailServiceTranslator $translator EmailServiceTranslator
     */
    public function __construct(
        EmailServiceClient $client,
        EmailServiceTranslator $translator
    ) {
        $this->client     = $client;
        $this->translator = $translator;
    }

    /**
     * @param string    $templateId TemplateID
     * @param Email     $email      Email
     * @param array     $data       Data
     * @param Overrides $overrides  Overrides
     * @param SessionId $sessionId  SessionId
     * @param string    $senderId   SenderId
     * @return SendEmailResponse
     * @throws \Exception
     */
    public function send(
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ): SendEmailResponse {
        $sendEmailResponseDto = $this->client->send($templateId, $email, $data, $overrides, $sessionId, $senderId);

        return $this->translator->translate($sendEmailResponseDto);
    }
}
