<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Domain\Services;

use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Overrides;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\SendEmailResponse;

interface EmailService
{
    /**
     * @param string    $templateId TemplateID
     * @param Email     $email      Email
     * @param array     $data       Data
     * @param Overrides $overrides  Overrides
     * @param SessionId $sessionId  SessionId
     * @param string    $senderId   SenderId
     * @return SendEmailResponse
     */
    public function send(
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ): SendEmailResponse;
}
