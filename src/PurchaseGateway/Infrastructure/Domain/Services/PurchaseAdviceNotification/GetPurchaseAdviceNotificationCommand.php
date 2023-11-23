<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\PurchaseAdviceNotification;

use ProBillerNG\CircuitBreaker\ExternalCommand;
use ProBillerNG\Logger\Log;
use \ProBillerNG\PurchaseGateway\Domain\Services\AdviceNotificationAdapter;

class GetPurchaseAdviceNotificationCommand extends ExternalCommand
{

    /** @var AdviceNotificationAdapter */
    private $adapter;

    /** @var string */
    private $siteId;

    /** @var string */
    private $taxType;

    /** @var string */
    private $sessionId;

    /** @var string */
    private $billerName;

    /** @var string */
    private $memberType;

    /** @var string */
    private $transactionId;

    /**
     * GetPurchaseAdviceNotificationCommand constructor.
     * @param AdviceNotificationAdapter $adapter       Purchase Advice Notification Adapter
     * @param string                    $siteId        Site Id.
     * @param string                    $taxType       Tax Type.
     * @param string                    $sessionId     SessionId
     * @param string                    $billerName    Biller Name.
     * @param string                    $memberType    MemberType
     * @param string                    $transactionId Transaction Id
     */
    public function __construct(
        AdviceNotificationAdapter $adapter,
        string $siteId,
        string $taxType,
        string $sessionId,
        string $billerName,
        string $memberType,
        string $transactionId
    ) {
        $this->adapter       = $adapter;
        $this->siteId        = $siteId;
        $this->taxType       = $taxType;
        $this->sessionId     = $sessionId;
        $this->billerName    = $billerName;
        $this->memberType    = $memberType;
        $this->transactionId = $transactionId;
    }

    /**
     * The code to be executed
     *
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function run(): bool
    {
        return $this->adapter->getAdvice(
            $this->siteId,
            $this->taxType,
            $this->sessionId,
            $this->billerName,
            $this->memberType,
            $this->transactionId
        );
    }

    /**
     * @return bool
     * @throws \ProBillerNG\Logger\Exception
     */
    protected function getFallback(): bool
    {
        Log::info('PANS error. Returning default value into circuit breaker.');
        $exception = $this->getExecutionException();
        if ($exception instanceof \Throwable) {
            Log::logException($exception);
        }

        return false;
    }
}
