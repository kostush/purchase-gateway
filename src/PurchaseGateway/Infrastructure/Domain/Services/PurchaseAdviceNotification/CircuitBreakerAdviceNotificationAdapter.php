<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use \ProBillerNG\PurchaseGateway\Domain\Services\AdviceNotificationAdapter;

class CircuitBreakerAdviceNotificationAdapter extends CircuitBreaker implements AdviceNotificationAdapter
{
    /** @var AdviceNotificationAdapter */
    private $adapter;

    /**
     * CircuitBreakerPurchaseAdviceNotificationAdapter constructor.
     * @param CommandFactory            $commandFactory Command Factory.
     * @param AdviceNotificationAdapter $adapter        Purchase Advice NotificationAdapter.
     */
    public function __construct(
        CommandFactory $commandFactory,
        AdviceNotificationAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param string $siteId        Site Id.
     * @param string $taxType       Tax Type.
     * @param string $sessionId     SessionId
     * @param string $billerName    Biller Name.
     * @param string $memberType    MemberType
     * @param string $transactionId Transaction Id
     * @return bool
     */
    public function getAdvice(
        string $siteId,
        string $taxType,
        string $sessionId,
        string $billerName,
        string $memberType,
        string $transactionId
    ): bool {
        $command = $this->commandFactory->getCommand(
            GetPurchaseAdviceNotificationCommand::class,
            $this->adapter,
            $siteId,
            $taxType,
            $sessionId,
            $billerName,
            $memberType,
            $transactionId
        );

        return $command->execute();
    }
}
