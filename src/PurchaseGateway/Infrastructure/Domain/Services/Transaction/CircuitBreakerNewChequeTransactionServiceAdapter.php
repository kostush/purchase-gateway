<?php
declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\NewChequePerformTransactionInterfaceAdapter;

class CircuitBreakerNewChequeTransactionServiceAdapter extends CircuitBreaker implements NewChequePerformTransactionInterfaceAdapter
{
    /**
     * @var NewChequePerformTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerNewCardTransactionServiceAdapter constructor.
     *
     * @param CommandFactory                     $commandFactory Command
     * @param NewChequePerformTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        NewChequePerformTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param SiteId            $siteId
     * @param Biller            $biller
     * @param CurrencyCode      $currencyCode
     * @param UserInfo          $userInfo
     * @param ChargeInformation $chargeInformation
     * @param PaymentInfo       $paymentInfo
     * @param BillerMapping     $billerMapping
     * @param SessionId         $sessionId
     *
     * @return Transaction
     */
    public function performTransaction(
        SiteId $siteId,
        Biller $biller,
        CurrencyCode $currencyCode,
        UserInfo $userInfo,
        ChargeInformation $chargeInformation,
        PaymentInfo $paymentInfo,
        BillerMapping $billerMapping,
        SessionId $sessionId
    ): Transaction {
        $command = $this->commandFactory->getCommand(
            CreatePerformTransactionCommand::class,
            $this->adapter,
            $siteId,
            $biller,
            $currencyCode,
            $userInfo,
            $chargeInformation,
            $paymentInfo,
            $billerMapping,
            $sessionId,
            null
        );

        return $command->execute();
    }
}