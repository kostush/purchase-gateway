<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Api\EmailApi;
use CommonServices\EmailServiceClient\Model\SendEmailRequestDto;
use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use MindGeek\Aad\AzureActiveDirectoryHelper;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\ServiceClient;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Application\Services\AzureActiveDirectoryAccessToken;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions\ClientCouldNotSendEmailServiceException;

class EmailServiceClient extends ServiceClient
{
    const TEMPLATE_ID = 'templateId';
    const TO_EMAIL    = 'toEmail';
    const DATA        = 'data';
    const OVERRIDES   = 'overrides';
    const SESSION_ID  = 'sessionId';
    const SENDER_ID   = 'senderId';

    /** @var EmailApi */
    protected $emailApi;

    /**
     * EmailServiceClient constructor.
     * @param EmailApi $emailApi EmailApi
     */
    public function __construct(EmailApi $emailApi)
    {
        $this->emailApi = $emailApi;
    }

    /**
     * @return string|null
     * @throws \ProBillerNG\Logger\Exception
     */
    private function generateToken(): ?string
    {
        $azureADToken = new AzureActiveDirectoryAccessToken(
            config('clientapis.emailService.aadAuth.clientId'),
            config('clientapis.emailService.aadAuth.tenant')
        );

        return $azureADToken->getToken(
            config('clientapis.emailService.aadAuth.clientSecret'),
            config('clientapis.emailService.aadAuth.resource')
        );
    }

    /**
     * @param string    $templateId TemplateId
     * @param Email     $email      Email
     * @param array     $data       Data
     * @param Overrides $overrides  Overrides
     * @param SessionId $sessionId  SessionId
     * @param string    $senderId   SenderId
     * @return SendEmailResponseDto
     * @throws \Exception
     */
    public function send(
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ): SendEmailResponseDto {
        try {
            $this->emailApi->getConfig()->setApiKey('Authorization', $this->generateToken());

            $dto = new SendEmailRequestDto(
                [
                    self::TEMPLATE_ID => $templateId,
                    self::TO_EMAIL    => (string) $email,
                    self::DATA        => $data,
                    self::OVERRIDES   => $overrides->toArray(),
                    self::SESSION_ID  => (string) $sessionId,
                    self::SENDER_ID   => $senderId
                ]
            );
            Log::info(
                'Request sent to Email Service',
                ['dto' => (string) $dto]
            );
            return $this->emailApi->sendEmail($dto);
        } catch (\Exception $e) {

            Log::warning(
                'SendEmailHandler Email sending failed.',
                [
                    'sessionId'     => (string) $sessionId,
                    'errorMessage'  => $e->getMessage()
                ]
            );

            throw new ClientCouldNotSendEmailServiceException($e);
        }
    }
}
