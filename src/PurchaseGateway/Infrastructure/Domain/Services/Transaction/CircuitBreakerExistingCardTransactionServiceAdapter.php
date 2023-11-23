<?php

declare(strict_types=1);

namespace ProBillerNG\PurchaseGateway\Infrastructure\Domain\Services\Transaction;

use Odesk\Phystrix\CommandFactory;
use ProBillerNG\CircuitBreaker\CircuitBreaker;
use ProBillerNG\PurchaseGateway\Domain\Model\Biller;
use ProBillerNG\PurchaseGateway\Domain\Model\BillerMapping;
use ProBillerNG\PurchaseGateway\Domain\Model\BinRouting;
use ProBillerNG\PurchaseGateway\Domain\Model\ChargeInformation;
use ProBillerNG\PurchaseGateway\Domain\Model\CurrencyCode;
use ProBillerNG\PurchaseGateway\Domain\Model\PaymentInfo;
use ProBillerNG\PurchaseGateway\Domain\Model\SessionId;
use ProBillerNG\PurchaseGateway\Domain\Model\SiteId;
use ProBillerNG\PurchaseGateway\Domain\Model\Transaction;
use ProBillerNG\PurchaseGateway\Domain\Model\UserInfo;
use ProBillerNG\PurchaseGateway\Domain\Services\ExistingCardPerformTransactionInterfaceAdapter;

class CircuitBreakerExistingCardTransactionServiceAdapter extends CircuitBreaker implements ExistingCardPerformTransactionInterfaceAdapter
{
    /**
     * @var ExistingCardPerformTransactionAdapter
     */
    private $adapter;

    /**
     * CircuitBreakerExistingCardTransactionServiceAdapter constructor.
     * @param CommandFactory                        $commandFactory Command
     * @param ExistingCardPerformTransactionAdapter $adapter        Adapter
     */
    public function __construct(
        CommandFactory $commandFactory,
        ExistingCardPerformTransactionAdapter $adapter
    ) {
        parent::__construct($commandFactory);
        $this->adapter = $adapter;
    }

    /**
     * @param SiteId            $siteId            Site Id
     * @param Biller            $biller            Biller
     * @param CurrencyCode      $currencyCode      Currency code
     * @param UserInfo          $userInfo          User info
     * @param ChargeInformation $chargeInformation Charge information
     * @param PaymentInfo       $paymentInfo       Payment info
     * @param BillerMapping     $billerMapping     Biller mapping
     * @param SessionId         $sessionId         Session Id
     * @param BinRouting|null   $binRouting        Bin routing
     * @param bool              $useThreeD         Perform transaction using 3DS
     * @param string|null       $returnUrl         Return Url
     * @param bool              $isNSFSupported    Flag to show if NSF is supported or not
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
        SessionId $sessionId,
        ?BinRouting $binRouting,
        bool $useThreeD = false,
        ?string $returnUrl = null,
        bool $isNSFSupported = false
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
            $binRouting,
            $useThreeD,
            $returnUrl,
            $isNSFSupported
        );

        return $command->execute();
    }
}
