<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use CommonServices\EmailServiceClient\Model\SendEmailResponseDto;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService\Exceptions\UndefinedTranslationObjectGiven;

class EmailServiceTranslator
{
    /**
     * @param object $result Result
     * @return mixed|SendEmailResponse
     * @throws \Exception
     */
    public function translate($result)
    {
        switch (get_class($result)) {
            case SendEmailResponseDto::class:
                /** @var $result SendEmailResponseDto */
                return SendEmailResponse::create(
                    SessionId::createFromString($result->getSessionId()),
                    $result->getTraceId()
                );
            default:
                throw new UndefinedTranslationObjectGiven();
        }
    }
}
