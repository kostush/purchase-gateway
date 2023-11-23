<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\EmailService;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\Email;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Services\EmailAdapter;

class CircuitBreakerEmailServiceAdapter extends CircuitBreaker implements EmailAdapter
{
    /**
     * @var EmailAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerEmailServiceAdapter constructor.
     * @param CommandFactory      $commandFactory Command
     * @param EmailServiceAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        EmailServiceAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string    $templateId Template Id
     * @param Email     $email      E-mail
     * @param array     $data       Data
     * @param Overrides $overrides  Overrides
     * @param SessionId $sessionId  Session Id
     * @param string    $senderId   Sender Id
     * @return SendEmailResponse
     */
    public function send(
        string $templateId,
        Email $email,
        array $data,
        Overrides $overrides,
        SessionId $sessionId,
        string $senderId
    ): SendEmailResponse {
        $command = $this->commandFactory->getCommand(
            CreateSendEmailCommand::class,
            $this->adapter,
            $templateId,
            $email,
            $data,
            $overrides,
            $sessionId,
            $senderId
        );

        return $command->execute();
    }
}
