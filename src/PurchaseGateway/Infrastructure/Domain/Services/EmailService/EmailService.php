<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\Logger\Exception;
use ProBillerNG\Logger\Log;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailAdapter;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailService as DomainEmailService;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions\CouldNotSendEmailException;

class EmailService implements DomainEmailService
{
    /** @var EmailAdapter */
    private $adapter;

    /**
     * EmailService constructor.
     * @param EmailAdapter $emailServiceAdapter EmailServiceAdapter
     */
    public function __construct(EmailAdapter $emailServiceAdapter)
    {
        $this->adapter = $emailServiceAdapter;
    }

    /**
     * @param string    $templateId TemplateID
     * @param Email     $email      Email
     * @param array     $data       Data
     * @param Overrides $overrides  Overrides
     * @param SessionId $sessionId  SessionId
     * @param string    $senderId   SenderId
     * @return SendEmailResponse
     * @throws CouldNotSendEmailException
     * @throws Exception
     */
    public function send(
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ): SendEmailResponse {
        try {
            return $this->adapter->send($templateId, $email, $data, $overrides, $sessionId, $senderId);
        } catch (\Exception $e) {
            Log::error(
                'EmailService sending of email failed',
                [
                    'errorMessage' => $e->getMessage(),
                    'email'        => (string) $email,
                    'data'         => $data,
                    'sessionId'    => (string) $sessionId,
                    'templateId'   => $templateId
                ]
            );
            throw new CouldNotSendEmailException($e);
        }
    }
}
