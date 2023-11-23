<?php

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;

class CreateSendEmailCommand extends ExternalCommand
{
    /** @var EmailServiceAdapter */
    private $adapter;

    /** @var string */
    private $templateId;

    /** @var Email */
    private $email;

    /** @var array */
    private $data;

    /** @var Overrides */
    private $overrides;

    /** @var SessionId */
    private $sessionId;

    /** @var string */
    private $senderId;

    /**
     * CreateSendEmailCommand constructor.
     * @param EmailServiceAdapter $adapter    Adapter
     * @param string              $templateId Template Id
     * @param Email               $email      E-mail
     * @param array               $data       Data
     * @param Overrides           $overrides  Overrides
     * @param SessionId           $sessionId  Session Id
     * @param string              $senderId   Sender Id
     */
    public function __construct(
        EmailServiceAdapter $adapter,
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ) {
        $this->adapter    = $adapter;
        $this->templateId = $templateId;
        $this->email      = $email;
        $this->data       = $data;
        $this->overrides  = $overrides;
        $this->sessionId  = $sessionId;
        $this->senderId   = $senderId;
    }

    /**
     * @return SendEmailResponse
     * @throws \Exception
     */
    protected function run(): SendEmailResponse
    {
        return $this->adapter->send(
            $this->templateId,
            $this->email,
            $this->data,
            $this->overrides,
            $this->sessionId,
            $this->senderId
        );
    }
}
